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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use UnexpectedValueException;

final readonly class PathaoShippingProvider implements ShippingProvider
{
    public const CODE = 'pathao';

    public function __construct(
        private string $baseUrl,
        private ?string $staticAccessToken,
        private ?string $clientId,
        private ?string $clientSecret,
        private ?string $username,
        private ?string $password,
        private int $storeId,
        private int $deliveryType = 48,
        private int $itemType = 2,
        private float $defaultItemWeightKg = 0.5,
        private int $connectTimeoutSeconds = 5,
        private int $timeoutSeconds = 15,
    ) {}

    public function code(): string
    {
        return self::CODE;
    }

    /**
     * Verify Pathao authentication and return the
     * merchant stores visible to the account.
     *
     * @return list<array{
     *     store_id: int,
     *     store_name: string,
     *     is_active: bool
     * }>
     */
    public function testConnection(): array
    {
        $response =
            $this->getStores();

        /*
         * Issued-token mode may have an expired cached token.
         */
        if (
            $response->status() === 401
            &&
            $this->nullable(
                $this->staticAccessToken,
            ) === null
            &&
            $this->canIssueToken()
        ) {
            $this->forgetCachedToken();

            $response =
                $this->getStores();
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Pathao connection test failed.',
            );
        }

        $rows =
            $response->json(
                'data.data',
            );

        if (! is_array($rows)) {
            throw new RuntimeException(
                'Pathao connection test returned an invalid store response.',
            );
        }

        $stores = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $storeId =
                $this->integerValue(
                    $row[
                        'store_id'
                    ] ?? null,
                );

            if (
                $storeId === null ||
                $storeId <= 0
            ) {
                continue;
            }

            $storeName =
                $this->stringValue(
                    $row[
                        'store_name'
                    ] ?? null,
                ) ??
                'Pathao Store #'.$storeId;

            $isActive =
                $this->integerValue(
                    $row[
                        'is_active'
                    ] ?? null,
                );

            $stores[] = [
                'store_id' => $storeId,

                'store_name' => $storeName,

                'is_active' => $isActive === 1,
            ];
        }

        return $stores;
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

        $order = $shipment->order;

        if (! $order instanceof Order) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao submission requires a valid parent order.',
            ]);
        }

        $shippingMethod =
            $shipment->shippingMethod;

        if (! $shippingMethod instanceof ShippingMethod) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'Pathao submission requires a shipping method.',
            ]);
        }

        $payload = $this->createPayload(
            shipment: $shipment,

            order: $order,

            idempotencyKey: $idempotencyKey,
        );

        try {
            $response = $this->postCreateOrder(
                $payload,
            );
        } catch (ConnectionException $exception) {
            throw new ProviderSubmissionOutcomeUnknownException(
                'Pathao submission outcome is unknown after a network failure.',
                previous: $exception,
            );
        }

        if (
            $response->status() ===
            401 &&
            $this->canIssueToken()
        ) {
            $this->forgetCachedToken();

            try {
                $response = $this->postCreateOrder(
                    $payload,
                );
            } catch (ConnectionException $exception) {
                throw new ProviderSubmissionOutcomeUnknownException(
                    'Pathao submission outcome is unknown after a network failure.',
                    previous: $exception,
                );
            }
        }

        if (! $response->successful()) {
            if (
                $response->serverError() ||
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
                    'Pathao returned an ambiguous shipment submission response.',
                );
            }

            $message =
                $this->safePathaoErrorMessage(
                    $response,
                );

            throw ValidationException::withMessages([
                'provider' => $message,
            ]);
        }

        $externalReference =
            $this->stringValue(
                $response->json(
                    'data.consignment_id',
                ),
            );

        if ($externalReference === null) {
            throw new ProviderSubmissionOutcomeUnknownException(
                'Pathao accepted the request without returning a consignment reference.',
            );
        }

        return new ProviderShipmentSubmissionResult(
            externalReference: $externalReference,

            carrier: 'Pathao',

            service: 'Pathao Courier',

            trackingNumber: $externalReference,

            trackingUrl: $this->trackingUrl(
                shipment: $shipment,

                externalReference: $externalReference,
            ),

            metadata: [
                'merchant_order_id' => $this->stringValue(
                    $response->json(
                        'data.merchant_order_id',
                    ),
                ),

                'order_status' => $this->stringValue(
                    $response->json(
                        'data.order_status',
                    ),
                ),

                'delivery_fee' => $this->scalarValue(
                    $response->json(
                        'data.delivery_fee',
                    ),
                ),
            ],
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        $externalReference = trim(
            $externalReference,
        );

        if ($externalReference === '') {
            throw new UnexpectedValueException(
                'Pathao tracking requires a consignment reference.',
            );
        }

        $response = $this->getOrderInfo(
            $externalReference,
        );

        if (
            $response->status() ===
            401 &&
            $this->canIssueToken()
        ) {
            $this->forgetCachedToken();

            $response = $this->getOrderInfo(
                $externalReference,
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Pathao tracking synchronization failed.',
            );
        }

        $providerStatus =
            $this->stringValue(
                $response->json(
                    'data.order_status_slug',
                ),
            ) ??
            $this->stringValue(
                $response->json(
                    'data.order_status',
                ),
            );

        return new ProviderShipmentTrackingResult(
            providerStatus: $providerStatus,

            carrier: 'Pathao',

            service: 'Pathao Courier',

            trackingNumber: $externalReference,

            trackingUrl: $this->trackingUrl(
                shipment: $shipment,

                externalReference: $externalReference,
            ),

            metadata: [
                'order_status' => $this->stringValue(
                    $response->json(
                        'data.order_status',
                    ),
                ),

                'order_status_slug' => $this->stringValue(
                    $response->json(
                        'data.order_status_slug',
                    ),
                ),

                'updated_at' => $this->stringValue(
                    $response->json(
                        'data.updated_at',
                    ),
                ),

                'invoice_id' => $this->scalarValue(
                    $response->json(
                        'data.invoice_id',
                    ),
                ),
            ],
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        if ($providerStatus === null) {
            return null;
        }

        $status =
            $this->normalizeStatus(
                $providerStatus,
            );

        return match ($status) {
            /*
             * A newly-created Pathao order remains an
             * informational provider state. Do not start
             * local fulfilment merely because it says
             * "Pending".
             */
            'pickup requested',
            'assigned for pickup',
            'pickup in progress',
            'rider assigned' => ShipmentStatus::Processing,

            'picked',
            'picked up',
            'rider picked',
            'at the sorting hub',
            'in transit',
            'received at last mile hub',
            'assigned for delivery',
            'out for delivery' => ShipmentStatus::Shipped,

            'delivered' => ShipmentStatus::Delivered,

            /*
             * Only definitive completed-return states move
             * the internal shipment to Returned.
             *
             * "Return", "return in transit", etc. remain
             * unmapped until the parcel actually returns.
             */
            'returned',
            'returned to merchant' => ShipmentStatus::Returned,

            /*
             * 8.6D deliberately blocks automatic internal
             * cancellation even when a provider maps here.
             */
            'cancelled',
            'pickup cancelled' => ShipmentStatus::Cancelled,

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
        /*
         * 8.8D:
         *
         * Courier configuration belongs to the connected
         * Pathao merchant account, not the checkout
         * ShippingMethod.
         *
         * Checkout delivery pricing/service and physical
         * courier fulfilment are now intentionally separate.
         */
        $storeId =
            $this->storeId;

        if ($storeId <= 0) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao store ID is not configured.',
            ]);
        }

        $weight =
            $this->defaultItemWeightKg;

        if (
            $weight < 0.5 ||
            $weight > 10
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao parcel weight must be between 0.5 and 10 KG.',
            ]);
        }

        $deliveryType =
            $this->deliveryType;

        $itemType =
            $this->itemType;

        if (
            ! in_array(
                $deliveryType,
                [
                    12,
                    48,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao delivery type must be Normal (48) or On Demand (12).',
            ]);
        }

        if (
            ! in_array(
                $itemType,
                [
                    1,
                    2,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao item type must be Document (1) or Parcel (2).',
            ]);
        }

        $recipientName = trim(
            (string) $order
                ->delivery_recipient_name,
        );

        if ($recipientName === '') {
            throw ValidationException::withMessages([
                'delivery_recipient_name' => 'Pathao requires a recipient name.',
            ]);
        }

        $phone = $this->bangladeshPhone(
            (string) $order
                ->delivery_phone,
        );

        $address = $this->fullAddress(
            $order,
        );

        if (
            mb_strlen(
                $address,
            ) < 10
        ) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Pathao requires a complete delivery address.',
            ]);
        }

        if (
            mb_strlen(
                $address,
            ) > 220
        ) {
            throw ValidationException::withMessages([
                'delivery_address' => 'The delivery address is too long for Pathao.',
            ]);
        }

        $itemQuantity = (int) $shipment
            ->items
            ->sum(
                static fn (
                    ShipmentItem $item,
                ): int => $item->quantity,
            );

        if ($itemQuantity <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Pathao submission requires at least one shipment item.',
            ]);
        }

        $amountToCollect =
            $this->amountToCollect(
                shipment: $shipment,

                order: $order,
            );

        $payload = [
            'store_id' => $storeId,

            /*
             * Pathao does not currently document a dedicated
             * idempotency header. Use the stable 8.6B key to
             * derive a deterministic merchant-order ID.
             */
            'merchant_order_id' => sprintf(
                'MWT-S%d-%s',
                $shipment->id,
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

            'recipient_name' => $recipientName,

            'recipient_phone' => $phone,

            /*
             * Pathao announced address auto-resolution for
             * API merchants, so city/zone/area IDs are not
             * required by this adapter.
             */
            'recipient_address' => $address,

            'delivery_type' => $deliveryType,

            'item_type' => $itemType,

            'item_quantity' => $itemQuantity,

            'item_weight' => $weight,

            'amount_to_collect' => $amountToCollect,
        ];

        $description =
            $this->itemDescription(
                $shipment,
            );

        if ($description !== null) {
            $payload['item_description'] =
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
                $order->payment_status,
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
            $order->payment_status !==
            PaymentStatus::Unpaid
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao COD collection is blocked for this payment state until the collectible balance is explicitly defined.',
            ]);
        }

        if (
            strtoupper(
                trim(
                    (string) $order->currency_code,
                ),
            ) !==
            'BDT'
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao COD collection currently supports BDT orders only.',
            ]);
        }

        if (
            ! $this->coversWholeOrder(
                shipment: $shipment,

                order: $order,
            )
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao COD submission is blocked for partial shipments until parcel-level COD allocation is configured.',
            ]);
        }

        if (
            $order->grand_total < 0 ||
            $order->grand_total % 100 !==
            0
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Pathao COD collection requires an order total expressed in whole BDT.',
            ]);
        }

        return intdiv(
            $order->grand_total,
            100,
        );
    }

    private function coversWholeOrder(
        Shipment $shipment,
        Order $order,
    ): bool {
        $shipmentQuantities = [];

        foreach (
            $shipment->items as $shipmentItem
        ) {
            $shipmentQuantities[
                $shipmentItem->order_item_id
            ] =
                (
                    $shipmentQuantities[
                        $shipmentItem
                            ->order_item_id
                    ] ?? 0
                ) +
                $shipmentItem->quantity;
        }

        if (
            $order->items->count() !==
            count(
                $shipmentQuantities,
            )
        ) {
            return false;
        }

        foreach (
            $order->items as $orderItem
        ) {
            if (
                ($shipmentQuantities[
                    $orderItem->id
                ] ?? 0) !==
                $orderItem->quantity
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
            $order->delivery_address_line_1,
            $order->delivery_address_line_2,
            $order->delivery_city,
            $order->delivery_state_or_region,
            $order->delivery_postal_code,
            $order->delivery_country_code,
        ];

        $normalized = [];

        foreach ($parts as $part) {
            if (! is_string($part)) {
                continue;
            }

            $part = trim(
                $part,
            );

            if ($part !== '') {
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
        $digits = preg_replace(
            '/\D+/',
            '',
            $phone,
        );

        if (! is_string($digits)) {
            $digits = '';
        }

        if (
            strlen(
                $digits,
            ) === 13 &&
            str_starts_with(
                $digits,
                '880',
            )
        ) {
            $digits =
                '0'.substr(
                    $digits,
                    3,
                );
        }

        if (
            strlen(
                $digits,
            ) !== 11 ||
            ! str_starts_with(
                $digits,
                '01',
            )
        ) {
            throw ValidationException::withMessages([
                'delivery_phone' => 'Pathao requires a valid 11-digit Bangladesh recipient phone number.',
            ]);
        }

        return $digits;
    }

    private function itemDescription(
        Shipment $shipment,
    ): ?string {
        $parts = [];

        foreach (
            $shipment->items as $shipmentItem
        ) {
            $name =
                $shipmentItem
                    ->orderItem
                    ?->product_name;

            if (
                ! is_string(
                    $name,
                ) ||
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

        if ($parts === []) {
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

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ConnectionException
     */
    private function postCreateOrder(
        array $payload,
    ): Response {
        return $this
            ->authorizedRequest()
            ->post(
                $this->url(
                    '/aladdin/api/v1/orders',
                ),
                $payload,
            );
    }

    /**
     * @throws ConnectionException
     */
    private function getStores(): Response
    {
        return $this
            ->authorizedRequest()
            ->get(
                $this->url(
                    '/aladdin/api/v1/stores',
                ),
            );
    }

    /**
     * @throws ConnectionException
     */
    private function getOrderInfo(
        string $externalReference,
    ): Response {
        return $this
            ->authorizedRequest()
            ->get(
                $this->url(
                    '/aladdin/api/v1/orders/'.
                    rawurlencode(
                        $externalReference,
                    ).
                    '/info',
                ),
            );
    }

    private function authorizedRequest(): PendingRequest
    {
        return $this
            ->request()
            ->withToken(
                $this->accessToken(),
            );
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
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

    private function accessToken(): string
    {
        $staticToken =
            $this->nullable(
                $this->staticAccessToken,
            );

        if ($staticToken !== null) {
            return $staticToken;
        }

        if (! $this->canIssueToken()) {
            throw new RuntimeException(
                'Pathao API credentials are not configured.',
            );
        }

        $cacheKey =
            $this->tokenCacheKey();

        $cached =
            Cache::get(
                $cacheKey,
            );

        if (
            is_string(
                $cached,
            ) &&
            trim(
                $cached,
            ) !== ''
        ) {
            return $cached;
        }

        $response = $this
            ->request()
            ->post(
                $this->url(
                    '/aladdin/api/v1/issue-token',
                ),
                [
                    'client_id' => $this->clientId,

                    'client_secret' => $this->clientSecret,

                    'username' => $this->username,

                    'password' => $this->password,

                    'grant_type' => 'password',
                ],
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Pathao authentication failed.',
            );
        }

        $token =
            $this->stringValue(
                $response->json(
                    'access_token',
                ),
            );

        if ($token === null) {
            throw new RuntimeException(
                'Pathao authentication did not return an access token.',
            );
        }

        $expiresIn =
            $this->integerValue(
                $response->json(
                    'expires_in',
                ),
            ) ??
            3600;

        $ttl = max(
            60,
            $expiresIn -
            120,
        );

        Cache::put(
            $cacheKey,
            $token,
            now()->addSeconds(
                $ttl,
            ),
        );

        return $token;
    }

    private function canIssueToken(): bool
    {
        return
            $this->nullable(
                $this->clientId,
            ) !== null &&
            $this->nullable(
                $this->clientSecret,
            ) !== null &&
            $this->nullable(
                $this->username,
            ) !== null &&
            $this->nullable(
                $this->password,
            ) !== null;
    }

    private function forgetCachedToken(): void
    {
        Cache::forget(
            $this->tokenCacheKey(),
        );
    }

    private function tokenCacheKey(): string
    {
        return 'shipping:pathao:access-token:'.
            hash(
                'sha256',
                $this->url(
                    '',
                ).
                '|'.
                (
                    $this->clientId ??
                    ''
                ),
            );
    }

    private function url(
        string $path,
    ): string {
        $baseUrl = trim(
            $this->baseUrl,
        );

        if (
            filter_var(
                $baseUrl,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new RuntimeException(
                'Pathao API base URL is invalid.',
            );
        }

        $scheme = strtolower(
            (string) parse_url(
                $baseUrl,
                PHP_URL_SCHEME,
            ),
        );

        $host = parse_url(
            $baseUrl,
            PHP_URL_HOST,
        );

        $user = parse_url(
            $baseUrl,
            PHP_URL_USER,
        );

        $pass = parse_url(
            $baseUrl,
            PHP_URL_PASS,
        );

        if (
            $scheme !== 'https' ||
            ! is_string($host) ||
            $host === '' ||
            $user !== null ||
            $pass !== null
        ) {
            throw new RuntimeException(
                'Pathao API base URL must use HTTPS without embedded credentials.',
            );
        }

        return rtrim(
            $baseUrl,
            '/',
        ).$path;
    }

    private function normalizeStatus(
        string $status,
    ): string {
        $status = strtolower(
            trim(
                $status,
            ),
        );

        $status = str_replace(
            [
                '_',
                '-',
            ],
            ' ',
            $status,
        );

        $status = preg_replace(
            '/\s+/',
            ' ',
            $status,
        );

        return is_string(
            $status,
        )
            ? trim(
                $status,
            )
            : '';
    }

    private function nullable(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            $value,
        );

        return $value === ''
            ? null
            : $value;
    }

    private function stringValue(
        mixed $value,
    ): ?string {
        if (
            ! is_string(
                $value,
            )
        ) {
            return null;
        }

        return $this->nullable(
            $value,
        );
    }

    private function integerValue(
        mixed $value,
    ): ?int {
        if (is_int($value)) {
            return $value;
        }

        if (
            is_string($value) &&
            ctype_digit(
                $value,
            )
        ) {
            return (int) $value;
        }

        return null;
    }

    private function scalarValue(
        mixed $value,
    ): string|int|float|bool|null {
        return is_scalar(
            $value,
        )
            ? $value
            : null;
    }

    private function trackingUrl(
        Shipment $shipment,
        string $externalReference,
    ): ?string {
        $shipment->loadMissing(
            'order',
        );

        $order =
            $shipment->order;

        if (! $order instanceof Order) {
            return null;
        }

        $phone =
            $this->trackingPhone(
                (string) $order
                    ->delivery_phone,
            );

        if ($phone === null) {
            return null;
        }

        return
            'https://merchant.pathao.com/tracking?'.
            http_build_query(
                [
                    'consignment_id' => trim(
                        $externalReference,
                    ),

                    'phone' => $phone,
                ],
                '',
                '&',
                PHP_QUERY_RFC3986,
            );
    }

    private function trackingPhone(
        string $phone,
    ): ?string {
        $digits =
            preg_replace(
                '/\D+/',
                '',
                $phone,
            );

        if (! is_string($digits)) {
            return null;
        }

        /*
         * +8801712345678
         * becomes
         * 01712345678
         */
        if (
            strlen($digits) === 13 &&
            str_starts_with(
                $digits,
                '880',
            )
        ) {
            $digits =
                '0'.substr(
                    $digits,
                    3,
                );
        }

        if (
            strlen($digits) !== 11 ||
            ! str_starts_with(
                $digits,
                '01',
            )
        ) {
            return null;
        }

        return $digits;
    }

    private function safePathaoErrorMessage(
        Response $response,
    ): string {
        $message =
            $this->stringValue(
                $response->json(
                    'message',
                ),
            );

        $errors =
            $response->json(
                'errors',
            );

        $parts = [];

        if ($message !== null) {
            $parts[] =
                $message;
        }

        if (is_array($errors)) {
            foreach (
                $errors as $field => $fieldErrors
            ) {
                if (! is_string($field)) {
                    continue;
                }

                if (is_string($fieldErrors)) {
                    $value =
                        trim(
                            $fieldErrors,
                        );

                    if ($value !== '') {
                        $parts[] =
                            $field.': '.$value;
                    }

                    continue;
                }

                if (! is_array($fieldErrors)) {
                    continue;
                }

                foreach (
                    $fieldErrors as $fieldError
                ) {
                    if (! is_string($fieldError)) {
                        continue;
                    }

                    $fieldError =
                        trim(
                            $fieldError,
                        );

                    if ($fieldError !== '') {
                        $parts[] =
                            $field.': '.$fieldError;
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

        if ($parts === []) {
            return sprintf(
                'Pathao rejected the shipment submission with HTTP %d.',
                $response->status(),
            );
        }

        return mb_substr(
            'Pathao: '.implode(
                ' | ',
                $parts,
            ),
            0,
            1000,
        );
    }
}
