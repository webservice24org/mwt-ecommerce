<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Providers;

use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use UnexpectedValueException;

final readonly class SteadfastShippingProvider implements ShippingProvider
{
    public const CODE = 'steadfast';

    private const PUBLIC_TRACKING_URL =
        'https://steadfast.com.bd/tracking';

    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private string $secretKey,
        private int $connectTimeoutSeconds = 5,
        private int $timeoutSeconds = 15,
    ) {}

    public function code(): string
    {
        return self::CODE;
    }

    public function testConnection(): float
    {
        $response =
            $this->request()
                ->get(
                    $this->url(
                        '/get_balance',
                    ),
                );

        if (
            ! $response->successful()
            ||
            $this->integerValue(
                $response->json(
                    'status',
                ),
            ) !== 200
        ) {
            throw new RuntimeException(
                'Steadfast connection test failed.',
            );
        }

        $balance =
            $response->json(
                'current_balance',
            );

        if (
            ! is_int(
                $balance,
            )
            &&
            ! is_float(
                $balance,
            )
            &&
            ! (
                is_string(
                    $balance,
                )
                &&
                is_numeric(
                    $balance,
                )
            )
        ) {
            throw new RuntimeException(
                'Steadfast connection test did not return a balance.',
            );
        }

        return (float) $balance;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        $shipment->loadMissing([
            'order.items',
            'shippingMethod',
            'items.orderItem',
        ]);

        $order =
            $shipment->order;

        if (
            ! $order instanceof Order
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Steadfast submission requires a valid parent order.',
            ]);
        }

        /*
         * The checkout delivery service remains independent
         * from the physical courier provider.
         */
        if (
            ! $shipment->shippingMethod
            instanceof ShippingMethod
        ) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'Steadfast submission requires a shipping method.',
            ]);
        }

        $payload =
            $this->createPayload(
                shipment: $shipment,

                order: $order,

                idempotencyKey: $idempotencyKey,
            );

        if (app()->environment('local')) {
            Log::debug(
                'Steadfast create_order request',
                [
                    'url' => $this->url(
                        '/create_order',
                    ),

                    /*
                     * NEVER log the actual credentials.
                     * Fingerprints let us confirm the runtime account.
                     */
                    'api_key' => [
                    'length' => strlen(
                        $this->apiKey,
                    ),

                    'fingerprint' => substr(
                        hash(
                            'sha256',
                            $this->apiKey,
                        ),
                        0,
                        12,
                    ),
                    ],

                    'secret_key' => [
                        'length' => strlen(
                            $this->secretKey,
                        ),

                        'fingerprint' => substr(
                            hash(
                                'sha256',
                                $this->secretKey,
                            ),
                            0,
                            12,
                        ),
                    ],

                    'payload' => [
                        'invoice' => $payload[
                            'invoice'
                        ] ?? null,

                        'recipient_name' => $payload[
                            'recipient_name'
                        ] ?? null,

                        'recipient_phone' => $this->maskedPhone(
                            $payload[
                                'recipient_phone'
                            ] ?? null,
                        ),

                        'recipient_address_length' => mb_strlen(
                            (string) (
                                $payload[
                                    'recipient_address'
                                ] ?? ''
                            ),
                        ),

                        'cod_amount' => $payload[
                            'cod_amount'
                        ] ?? null,

                        'item_description' => $payload[
                            'item_description'
                        ] ?? null,

                        'total_lot' => $payload[
                            'total_lot'
                        ] ?? null,

                        'delivery_type' => $payload[
                            'delivery_type'
                        ] ?? null,
                    ],
                ],
            );
        }

        try {
            $response =
                $this->request()
                    ->post(
                        $this->url(
                            '/create_order',
                        ),
                        $payload,
                    );
            if (app()->environment('local')) {
                Log::debug(
                    'Steadfast create_order response',
                    [
                        'status' => $response->status(),

                        'json' => $response->json(),

                        'body' => mb_substr(
                            $response->body(),
                            0,
                            2000,
                        ),
                    ],
                );
            }
        } catch (
            ConnectionException $exception
        ) {
            throw new ProviderSubmissionOutcomeUnknownException(
                'Steadfast submission outcome is unknown after a network failure.',
                previous: $exception,
            );
        }

        if (
            ! $response->successful()
        ) {
            if (
                $response->serverError()
                ||
                in_array(
                    $response->status(),
                    [
                        408,
                        429,
                    ],
                    true,
                )
            ) {
                throw new ProviderSubmissionOutcomeUnknownException(
                    'Steadfast returned an ambiguous shipment submission response.',
                );
            }

            if (
                $this->isDuplicateInvoiceResponse(
                    $response,
                )
            ) {
                throw new ProviderSubmissionOutcomeUnknownException(
                    'Steadfast reports that this provider invoice already exists. Manual provider review is required before retry.',
                );
            }

            throw ValidationException::withMessages([
                'provider' => $this->safeErrorMessage(
                    $response,
                ),
            ]);
        }

        if (
            $this->integerValue(
                $response->json(
                    'status',
                ),
            ) !== 200
        ) {
            throw ValidationException::withMessages([
                'provider' => $this->safeErrorMessage(
                    $response,
                ),
            ]);
        }

        $externalReference =
            $this->stringValue(
                $response->json(
                    'consignment.consignment_id',
                ),
            );

        $trackingCode =
            $this->stringValue(
                $response->json(
                    'consignment.tracking_code',
                ),
            );

        if (
            $externalReference === null
            ||
            $trackingCode === null
        ) {
            throw new ProviderSubmissionOutcomeUnknownException(
                'Steadfast accepted the request without returning complete consignment identifiers.',
            );
        }

        return new ProviderShipmentSubmissionResult(
            externalReference: $externalReference,

            carrier: 'Steadfast',

            service: 'Steadfast Courier',

            trackingNumber: $trackingCode,

            trackingUrl: self::PUBLIC_TRACKING_URL,

            metadata: [
                'invoice' => $this->stringValue(
                    $response->json(
                        'consignment.invoice',
                    ),
                ),

                'tracking_code' => $trackingCode,

                'provider_status' => $this->stringValue(
                    $response->json(
                        'consignment.status',
                    ),
                ),

                'cod_amount' => $this->scalarValue(
                    $response->json(
                        'consignment.cod_amount',
                    ),
                ),
            ],
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        $externalReference =
            trim(
                $externalReference,
            );

        if (
            $externalReference === ''
        ) {
            throw new UnexpectedValueException(
                'Steadfast tracking requires a consignment reference.',
            );
        }

        try {
            $response =
                $this->request()
                    ->get(
                        $this->url(
                            '/status_by_cid/'
                            .rawurlencode(
                                $externalReference,
                            ),
                        ),
                    );
        } catch (
            ConnectionException $exception
        ) {
            throw new RuntimeException(
                'Steadfast tracking synchronization failed.',
                previous: $exception,
            );
        }

        if (
            ! $response->successful()
            ||
            $this->integerValue(
                $response->json(
                    'status',
                ),
            ) !== 200
        ) {
            throw new RuntimeException(
                'Steadfast tracking synchronization failed.',
            );
        }

        $providerStatus =
            $this->stringValue(
                $response->json(
                    'delivery_status',
                ),
            );

        $trackingNumber =
            is_string(
                $shipment->tracking_number,
            )
            &&
            trim(
                $shipment->tracking_number,
            ) !== ''
            ? trim(
                $shipment->tracking_number,
            )
            : $externalReference;

        return new ProviderShipmentTrackingResult(
            providerStatus: $providerStatus,

            carrier: 'Steadfast',

            service: 'Steadfast Courier',

            trackingNumber: $trackingNumber,

            trackingUrl: self::PUBLIC_TRACKING_URL,

            metadata: [
                'delivery_status' => $providerStatus,

                'consignment_id' => $externalReference,
            ],
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        if (
            $providerStatus === null
        ) {
            return null;
        }

        return match (
            strtolower(
                trim(
                    $providerStatus,
                ),
            )
        ) {
            'delivered' => ShipmentStatus::Delivered,

            'cancelled' => ShipmentStatus::Cancelled,

            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function createPayload(
        Shipment $shipment,
        Order $order,
        string $idempotencyKey,
    ): array {
        $recipientName =
            trim(
                (string) $order
                    ->delivery_recipient_name,
            );

        if (
            $recipientName === ''
            ||
            mb_strlen(
                $recipientName,
            ) > 100
        ) {
            throw ValidationException::withMessages([
                'delivery_recipient_name' => 'Steadfast requires a recipient name of 100 characters or fewer.',
            ]);
        }

        $phone =
            $this->bangladeshPhone(
                (string) $order
                    ->delivery_phone,
            );

        $address =
            $this->fullAddress(
                $order,
            );

        if (
            mb_strlen(
                $address,
            ) < 10
            ||
            mb_strlen(
                $address,
            ) > 250
        ) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Steadfast requires a complete delivery address of 10 to 250 characters.',
            ]);
        }

        $itemQuantity =
            (int) $shipment
                ->items
                ->sum(
                    static fn (
                        ShipmentItem $item,
                    ): int => $item
                        ->quantity,
                );

        if (
            $itemQuantity <= 0
        ) {
            throw ValidationException::withMessages([
                'items' => 'Steadfast submission requires at least one shipment item.',
            ]);
        }

        /*
         * Stable provider invoice derived from the
         * submission idempotency key.
         */
        $invoice =
            sprintf(
                'MWT-S%d-%s',
                $shipment->id,
                strtoupper(
                    substr(
                        str_replace(
                            '-',
                            '',
                            $idempotencyKey,
                        ),
                        0,
                        12,
                    ),
                ),
            );

        $payload = [
            'invoice' => $invoice,

            'recipient_name' => $recipientName,

            'recipient_phone' => $phone,

            'recipient_address' => $address,

            'cod_amount' => $this->amountToCollect(
                $shipment,
                $order,
            ),

            'total_lot' => $itemQuantity,

            /*
             * Steadfast:
             * 0 = Home Delivery
             * 1 = Point Delivery
             */
            'delivery_type' => 0,
        ];

        $description =
            $this->itemDescription(
                $shipment,
            );

        if (
            $description !== null
        ) {
            $payload[
                'item_description'
            ] =
                $description;
        }

        return $payload;
    }

    private function amountToCollect(
        Shipment $shipment,
        Order $order,
    ): int {
        if (
            in_array(
                $order
                    ->payment_status,
                [
                    PaymentStatus::Paid,
                    PaymentStatus::Refunded,
                ],
                true,
            )
        ) {
            return 0;
        }

        if (
            $order
                ->payment_status !==
            PaymentStatus::Unpaid
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Steadfast COD collection is blocked for this payment state until the collectible balance is explicitly defined.',
            ]);
        }

        if (
            strtoupper(
                trim(
                    (string) $order
                        ->currency_code,
                ),
            ) !==
            'BDT'
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Steadfast COD collection currently supports BDT orders only.',
            ]);
        }

        if (
            ! $this->coversWholeOrder(
                $shipment,
                $order,
            )
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Steadfast COD submission is blocked for partial shipments until parcel-level COD allocation is configured.',
            ]);
        }

        if (
            $order
                ->grand_total < 0
            ||
            $order
                ->grand_total % 100 !==
            0
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Steadfast COD collection requires an order total expressed in whole BDT.',
            ]);
        }

        return intdiv(
            $order
                ->grand_total,
            100,
        );
    }

    private function coversWholeOrder(
        Shipment $shipment,
        Order $order,
    ): bool {
        $shipmentQuantities =
            [];

        foreach (
            $shipment
                ->items as $shipmentItem
        ) {
            $shipmentQuantities[
                $shipmentItem
                    ->order_item_id
            ] =
                (
                    $shipmentQuantities[
                        $shipmentItem
                            ->order_item_id
                    ] ?? 0
                )
                +
                $shipmentItem
                    ->quantity;
        }

        if (
            $order
                ->items
                ->count()
            !==
            count(
                $shipmentQuantities,
            )
        ) {
            return false;
        }

        foreach (
            $order
                ->items as $orderItem
        ) {
            if (
                (
                    $shipmentQuantities[
                        $orderItem
                            ->id
                    ] ?? 0
                )
                !==
                $orderItem
                    ->quantity
            ) {
                return false;
            }
        }

        return true;
    }

    private function fullAddress(
        Order $order,
    ): string {
        $parts = [
            $order
                ->delivery_address_line_1,

            $order
                ->delivery_address_line_2,

            $order
                ->delivery_city,

            $order
                ->delivery_state_or_region,

            $order
                ->delivery_postal_code,

            $order
                ->delivery_country_code,
        ];

        $normalized =
            [];

        foreach (
            $parts as $part
        ) {
            if (
                ! is_string(
                    $part,
                )
            ) {
                continue;
            }

            $part =
                trim(
                    $part,
                );

            if (
                $part !== ''
            ) {
                $normalized[] =
                    $part;
            }
        }

        return implode(
            ', ',
            $normalized,
        );
    }

    private function bangladeshPhone(
        string $phone,
    ): string {
        $digits =
            preg_replace(
                '/\D+/',
                '',
                $phone,
            );

        $digits =
            is_string(
                $digits,
            )
            ? $digits
            : '';

        if (
            strlen(
                $digits,
            ) === 13
            &&
            str_starts_with(
                $digits,
                '880',
            )
        ) {
            $digits =
                '0'
                .substr(
                    $digits,
                    3,
                );
        }

        if (
            strlen(
                $digits,
            ) !== 11
            ||
            ! str_starts_with(
                $digits,
                '01',
            )
        ) {
            throw ValidationException::withMessages([
                'delivery_phone' => 'Steadfast requires a valid 11-digit Bangladesh recipient phone number.',
            ]);
        }

        return $digits;
    }

    private function itemDescription(
        Shipment $shipment,
    ): ?string {
        $parts =
            [];

        foreach (
            $shipment
                ->items as $shipmentItem
        ) {
            $name =
                $shipmentItem
                    ->orderItem
                    ?->product_name;

            if (
                ! is_string(
                    $name,
                )
                ||
                trim(
                    $name,
                ) === ''
            ) {
                continue;
            }

            $parts[] =
                sprintf(
                    '%s x%d',
                    trim(
                        $name,
                    ),
                    $shipmentItem
                        ->quantity,
                );
        }

        if (
            $parts === []
        ) {
            return null;
        }

        return mb_substr(
            implode(
                ', ',
                $parts,
            ),
            0,
            255,
        );
    }

    private function request(
    ): PendingRequest {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Api-Key' => $this->apiKey,

                'Secret-Key' => $this->secretKey,
            ])
            ->connectTimeout(
                max(
                    1,
                    $this->connectTimeoutSeconds,
                ),
            )
            ->timeout(
                max(
                    1,
                    $this->timeoutSeconds,
                ),
            );
    }

    private function url(
        string $path,
    ): string {
        $baseUrl =
            rtrim(
                trim(
                    $this->baseUrl,
                ),
                '/',
            );

        if (
            filter_var(
                $baseUrl,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new RuntimeException(
                'Steadfast base URL is invalid.',
            );
        }

        $scheme =
            strtolower(
                (string) parse_url(
                    $baseUrl,
                    PHP_URL_SCHEME,
                ),
            );

        $user =
            parse_url(
                $baseUrl,
                PHP_URL_USER,
            );

        $password =
            parse_url(
                $baseUrl,
                PHP_URL_PASS,
            );

        if (
            $scheme !== 'https'
            ||
            $user !== null
            ||
            $password !== null
        ) {
            throw new RuntimeException(
                'Steadfast base URL must use HTTPS without embedded credentials.',
            );
        }

        return
            $baseUrl
            .'/'
            .ltrim(
                $path,
                '/',
            );
    }

    private function safeErrorMessage(
        Response $response,
    ): string {
        $parts =
            [];

        foreach (
            [
                'message',
                'error',
            ] as $key
        ) {
            $value =
                $this->stringValue(
                    $response
                        ->json(
                            $key,
                        ),
                );

            if (
                $value !== null
            ) {
                $parts[] =
                    $value;
            }
        }

        $errors =
            $response
                ->json(
                    'errors',
                );

        if (
            is_array(
                $errors,
            )
        ) {
            foreach (
                $errors as $field => $messages
            ) {
                if (
                    ! is_string(
                        $field,
                    )
                ) {
                    continue;
                }

                $messages =
                    is_string(
                        $messages,
                    )
                    ? [
                        $messages,
                    ]
                    : $messages;

                if (
                    ! is_array(
                        $messages,
                    )
                ) {
                    continue;
                }

                foreach (
                    $messages as $message
                ) {
                    if (
                        is_string(
                            $message,
                        )
                        &&
                        trim(
                            $message,
                        ) !== ''
                    ) {
                        $parts[] =
                            $field
                            .': '
                            .trim(
                                $message,
                            );
                    }
                }
            }
        }

        $parts =
            array_values(
                array_unique(
                    $parts,
                ),
            );

        if (
            $parts === []
        ) {
            return sprintf(
                'Steadfast rejected the shipment submission with HTTP %d.',
                $response
                    ->status(),
            );
        }

        return mb_substr(
            'Steadfast: '
            .implode(
                ' | ',
                $parts,
            ),
            0,
            1000,
        );
    }

    private function isDuplicateInvoiceResponse(
        Response $response,
    ): bool {
        $payload =
            $response
                ->json();

        if (
            ! is_array(
                $payload,
            )
        ) {
            return false;
        }

        $encoded =
            json_encode(
                $payload,
            );

        if (
            ! is_string(
                $encoded,
            )
        ) {
            return false;
        }

        $normalized =
            strtoupper(
                $encoded,
            );

        return
            str_contains(
                $normalized,
                'THIS_INVOICE_ALREADY_EXISTS',
            )
            ||
            str_contains(
                $normalized,
                'INVOICE ALREADY EXISTS',
            )
            ||
            str_contains(
                $normalized,
                'INVOICE_ALREADY_EXISTS',
            );
    }

    private function stringValue(
        mixed $value,
    ): ?string {
        if (
            ! is_string(
                $value,
            )
            &&
            ! is_int(
                $value,
            )
        ) {
            return null;
        }

        $value =
            trim(
                (string) $value,
            );

        return
            $value === ''
            ? null
            : $value;
    }

    private function integerValue(
        mixed $value,
    ): ?int {
        if (
            is_int(
                $value,
            )
        ) {
            return $value;
        }

        if (
            is_string(
                $value,
            )
            &&
            preg_match(
                '/^-?\d+$/',
                trim(
                    $value,
                ),
            ) === 1
        ) {
            return (int) $value;
        }

        return null;
    }

    private function scalarValue(
        mixed $value,
    ): string|int|float|bool|null {
        return
            is_string(
                $value,
            )
            ||
            is_int(
                $value,
            )
            ||
            is_float(
                $value,
            )
            ||
            is_bool(
                $value,
            )
            ||
            $value === null
            ? $value
            : null;
    }

    private function maskedPhone(
        mixed $phone,
    ): ?string {
        if (! is_string($phone)) {
            return null;
        }

        $phone =
            trim(
                $phone,
            );

        if (
            strlen(
                $phone,
            ) < 7
        ) {
            return '***';
        }

        return
            substr(
                $phone,
                0,
                3,
            )
            .'*****'
            .substr(
                $phone,
                -3,
            );
    }
}
