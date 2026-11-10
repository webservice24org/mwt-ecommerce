<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Domain\Shipping\Providers\SteadfastShippingProvider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SteadfastShippingProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shipment_sends_authoritative_steadfast_payload(): void
    {
        Http::fake([
            'https://steadfast.test/api/v1/create_order' => Http::response(
                [
                    'status' => 200,

                    'message' => 'Consignment has been created successfully.',

                    'consignment' => [
                        'consignment_id' => 131822154,

                        'invoice' => 'MWT-S1-111111112222',

                        'tracking_code' => '7DB724A65D4',

                        'recipient_name' => 'Customer Name',

                        'recipient_phone' => '01712345678',

                        'recipient_address' => 'House 10, Road 5, Dhanmondi, Dhaka',

                        'cod_amount' => 1250,

                        'status' => 'in_review',
                    ],
                ],
                200,
            ),
        ]);

        $shipment =
            $this->fullShipmentFixture();

        $result =
            $this->provider()
                ->createShipment(
                    shipment: $shipment,

                    idempotencyKey: '11111111-2222-4333-8444-555555555555',
                );

        $this->assertSame(
            '131822154',
            $result
                ->externalReference,
        );

        $this->assertSame(
            '7DB724A65D4',
            $result
                ->trackingNumber,
        );

        $this->assertSame(
            'Steadfast',
            $result
                ->carrier,
        );

        $this->assertSame(
            'https://steadfast.com.bd/tracking',
            $result
                ->trackingUrl,
        );

        Http::assertSent(
            static function (Request $request): bool {
                if (
                    $request->url() !==
                    'https://steadfast.test/api/v1/create_order'
                ) {
                    return false;
                }

                $data =
                    $request->data();

                return
                    $request->hasHeader(
                        'Api-Key',
                        'STEADFAST-API-KEY',
                    )
                    &&
                    $request->hasHeader(
                        'Secret-Key',
                        'STEADFAST-SECRET-KEY',
                    )
                    &&
                    is_string(
                        $data[
                            'invoice'
                        ] ?? null,
                    )
                    &&
                    str_starts_with(
                        $data[
                            'invoice'
                        ],
                        'MWT-S',
                    )
                    &&
                    ($data[
                        'recipient_name'
                    ] ?? null) ===
                    'Customer Name'
                    &&
                    ($data[
                        'recipient_phone'
                    ] ?? null) ===
                    '01712345678'
                    &&
                    ($data[
                        'cod_amount'
                    ] ?? null) ===
                    1250
                    &&
                    ($data[
                        'total_lot'
                    ] ?? null) ===
                    2
                    &&
                    str_contains(
                        (string) (
                            $data[
                                'item_description'
                            ] ?? ''
                        ),
                        'Test Product x2',
                    );
            },
        );
    }

    public function test_tracking_status_is_read_by_consignment_id(): void
    {
        Http::fake([
            'https://steadfast.test/api/v1/status_by_cid/131822154' => Http::response(
                [
                    'status' => 200,

                    'delivery_status' => 'delivered',
                ],
                200,
            ),
        ]);

        $shipment =
            $this->fullShipmentFixture();

        $shipment->update([
            'tracking_number' => '7DB724A65D4',
        ]);

        $result =
            $this->provider()
                ->refreshTracking(
                    shipment: $shipment->refresh(),

                    externalReference: '131822154',
                );

        $this->assertSame(
            'delivered',
            $result
                ->providerStatus,
        );

        $this->assertSame(
            '7DB724A65D4',
            $result
                ->trackingNumber,
        );

        $this->assertSame(
            ShipmentStatus::Delivered,
            $this->provider()
                ->mapTrackingStatus(
                    $result
                        ->providerStatus,
                ),
        );
    }

    public function test_approval_pending_and_partial_delivery_are_not_auto_mapped_to_delivered(): void
    {
        $provider =
            $this->provider();

        $this->assertNull(
            $provider->mapTrackingStatus(
                'delivered_approval_pending',
            ),
        );

        $this->assertNull(
            $provider->mapTrackingStatus(
                'partial_delivered',
            ),
        );

        $this->assertNull(
            $provider->mapTrackingStatus(
                'partial_delivered_approval_pending',
            ),
        );
    }

    public function test_ambiguous_create_response_requires_manual_review(): void
    {
        Http::fake([
            'https://steadfast.test/api/v1/create_order' => Http::response(
                [],
                504,
            ),
        ]);

        $this->expectException(
            ProviderSubmissionOutcomeUnknownException::class,
        );

        $this->provider()
            ->createShipment(
                shipment: $this->fullShipmentFixture(),

                idempotencyKey: '11111111-2222-4333-8444-555555555555',
            );
    }

    public function test_duplicate_invoice_response_requires_manual_review(): void
    {
        Http::fake([
            'https://steadfast.test/api/v1/create_order' => Http::response(
                [
                    'status' => 400,

                    'message' => 'THIS_INVOICE_ALREADY_EXISTS',
                ],
                400,
            ),
        ]);

        $this->expectException(
            ProviderSubmissionOutcomeUnknownException::class,
        );

        $this->provider()
            ->createShipment(
                shipment: $this->fullShipmentFixture(),

                idempotencyKey: '11111111-2222-4333-8444-555555555555',
            );
    }

    private function fullShipmentFixture(
    ): Shipment {
        $user =
            User::factory()
                ->create();

        /*
         * Checkout shipping method is deliberately Manual.
         * The physical courier provider is independently
         * selected as Steadfast by the shipment workflow.
         */
        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => 'manual',

                    'is_active' => true,
                ]);

        $order =
            Order::factory()
                ->for(
                    $user,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'payment_status' => PaymentStatus::Unpaid,

                    'fulfilment_status' => FulfilmentStatus::Processing,

                    'shipping_method_id' => $shippingMethod->id,

                    'shipping_method_code' => $shippingMethod->code,

                    'shipping_method_name' => $shippingMethod->name,

                    'currency_code' => 'BDT',

                    'grand_total' => 125000,

                    'delivery_recipient_name' => 'Customer Name',

                    'delivery_phone' => '+8801712345678',

                    'delivery_address_line_1' => 'House 10, Road 5',

                    'delivery_address_line_2' => 'Dhanmondi',

                    'delivery_city' => 'Dhaka',

                    'delivery_state_or_region' => 'Dhaka Division',

                    'delivery_postal_code' => '1205',

                    'delivery_country_code' => 'BD',
                ]);

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'product_name' => 'Test Product',

                    'quantity' => 2,
                ]);

        $shipment =
            Shipment::factory()
                ->for(
                    $order,
                )
                ->for(
                    $shippingMethod,
                    'shippingMethod',
                )
                ->create([
                    'status' => ShipmentStatus::Processing,
                ]);

        ShipmentItem::factory()
            ->for(
                $shipment,
            )
            ->for(
                $orderItem,
                'orderItem',
            )
            ->create([
                'quantity' => 2,
            ]);

        return $shipment;
    }

    private function provider(
    ): SteadfastShippingProvider {
        return new SteadfastShippingProvider(
            baseUrl: 'https://steadfast.test/api/v1',

            apiKey: 'STEADFAST-API-KEY',

            secretKey: 'STEADFAST-SECRET-KEY',

            connectTimeoutSeconds: 2,

            timeoutSeconds: 5,
        );
    }
}
