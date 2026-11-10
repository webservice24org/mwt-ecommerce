<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PathaoShippingProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_create_shipment_sends_authoritative_pathao_payload(): void
    {
        Http::fake([
            'https://pathao.test/aladdin/api/v1/orders' => Http::response(
                [
                    'message' => 'Order Created Successfully',

                    'type' => 'success',

                    'code' => 200,

                    'data' => [
                        'consignment_id' => 'PATHAO-1001',

                        'merchant_order_id' => 'MWT-REMOTE',

                        'order_status' => 'Pending',

                        'delivery_fee' => 80,
                    ],
                ],
                200,
            ),
        ]);

        [
            'shipment' => $shipment,

            'order' => $order,
        ] = $this->fullShipmentFixture();

        $provider = $this->provider(
            staticAccessToken: 'test-access-token',
        );

        $result = $provider->createShipment(
            shipment: $shipment,

            idempotencyKey: '11111111-2222-4333-8444-555555555555',
        );

        $this->assertSame(
            'PATHAO-1001',
            $result->externalReference,
        );

        $this->assertSame(
            'Pathao',
            $result->carrier,
        );

        $this->assertSame(
            'PATHAO-1001',
            $result->trackingNumber,
        );

        Http::assertSent(
            static function (Request $request) use ($order): bool {
                $data = $request->data();

                return
                    $request->url() ===
                    'https://pathao.test/aladdin/api/v1/orders' &&
                    $request->hasHeader(
                        'Authorization',
                        'Bearer test-access-token',
                    ) &&
                    ($data['store_id'] ?? null) ===
                    12345 &&
                    ($data['recipient_name'] ?? null) ===
                    'Customer Name' &&
                    ($data['recipient_phone'] ?? null) ===
                    '01712345678' &&
                    str_contains(
                        (string) (
                            $data['recipient_address'] ??
                            ''
                        ),
                        'Dhaka',
                    ) &&
                    ($data['delivery_type'] ?? null) ===
                    48 &&
                    ($data['item_type'] ?? null) ===
                    2 &&
                    ($data['item_quantity'] ?? null) ===
                    2 &&
                    ($data['item_weight'] ?? null) ===
                    0.5 &&
                    ($data['amount_to_collect'] ?? null) ===
                    intdiv(
                        $order->grand_total,
                        100,
                    );
            },
        );

        /*
         * Assert the deterministic merchant ID separately
         * without depending on request-object closure state.
         */
        Http::assertSent(
            static function (Request $request) use ($shipment): bool {
                $merchantOrderId =
                    $request->data()[
                        'merchant_order_id'
                    ] ?? null;

                return
                    is_string(
                        $merchantOrderId,
                    ) &&
                    str_starts_with(
                        $merchantOrderId,
                        'MWT-S'.$shipment->id.'-',
                    );
            },
        );
    }

    public function test_issue_token_is_cached_and_used_when_static_token_is_absent(): void
    {
        Http::fake([
            'https://pathao.test/aladdin/api/v1/issue-token' => Http::response(
                [
                    'access_token' => 'issued-token',

                    'refresh_token' => 'refresh-token',

                    'expires_in' => 3600,
                ],
                200,
            ),

            'https://pathao.test/aladdin/api/v1/orders/*/info' => Http::response(
                [
                    'data' => [
                        'consignment_id' => 'PATHAO-1002',

                        'order_status' => 'In Transit',

                        'order_status_slug' => 'In Transit',
                    ],
                ],
                200,
            ),
        ]);

        $provider = $this->provider(
            staticAccessToken: null,

            clientId: 'client-id',

            clientSecret: 'client-secret',

            username: 'merchant@example.test',

            password: 'secret-password',
        );

        [
            'shipment' => $shipment,
        ] = $this->fullShipmentFixture();

        $provider->refreshTracking(
            shipment: $shipment,

            externalReference: 'PATHAO-1002',
        );

        $provider->refreshTracking(
            shipment: $shipment,

            externalReference: 'PATHAO-1002',
        );

        Http::assertSentCount(
            3,
        );

        Http::assertSent(
            static fn (
                Request $request,
            ): bool => $request->url() ===
                'https://pathao.test/aladdin/api/v1/issue-token' &&
                ($request->data()['grant_type'] ?? null) ===
                'password',
        );
    }

    public function test_tracking_result_keeps_provider_status_separate_from_internal_state(): void
    {
        Http::fake([
            'https://pathao.test/aladdin/api/v1/orders/PATHAO-2001/info' => Http::response(
                [
                    'data' => [
                        'consignment_id' => 'PATHAO-2001',

                        'order_status' => 'Delivered',

                        'order_status_slug' => 'Delivered',

                        'updated_at' => '2026-11-05 15:00:00',

                        'invoice_id' => 'INV-1',
                    ],
                ],
                200,
            ),
        ]);

        [
            'shipment' => $shipment,
        ] = $this->fullShipmentFixture();

        $result = $this
            ->provider(
                staticAccessToken: 'token',
            )
            ->refreshTracking(
                shipment: $shipment,

                externalReference: 'PATHAO-2001',
            );

        $this->assertSame(
            'Delivered',
            $result->providerStatus,
        );

        $this->assertSame(
            'PATHAO-2001',
            $result->trackingNumber,
        );

        $this->assertSame(
            ShipmentStatus::Delivered,
            $this
                ->provider(
                    staticAccessToken: 'token',
                )
                ->mapTrackingStatus(
                    $result->providerStatus,
                ),
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment
                ->refresh()
                ->status,
        );
    }

    public function test_status_mapping_is_conservative_for_return_in_transit_and_partial_delivery(): void
    {
        $provider = $this->provider(
            staticAccessToken: 'token',
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $provider->mapTrackingStatus(
                'Pickup_Requested',
            ),
        );

        $this->assertSame(
            ShipmentStatus::Shipped,
            $provider->mapTrackingStatus(
                'At_the_Sorting_HUB',
            ),
        );

        $this->assertSame(
            ShipmentStatus::Delivered,
            $provider->mapTrackingStatus(
                'Delivered',
            ),
        );

        $this->assertSame(
            ShipmentStatus::Returned,
            $provider->mapTrackingStatus(
                'Returned_To_Merchant',
            ),
        );

        $this->assertSame(
            ShipmentStatus::Cancelled,
            $provider->mapTrackingStatus(
                'Pickup_Cancelled',
            ),
        );

        $this->assertNull(
            $provider->mapTrackingStatus(
                'Return_In_Transit',
            ),
        );

        $this->assertNull(
            $provider->mapTrackingStatus(
                'Partial_Delivery',
            ),
        );

        $this->assertNull(
            $provider->mapTrackingStatus(
                'Pending',
            ),
        );
    }

    public function test_partial_cod_shipment_is_blocked_before_http_request(): void
    {
        Http::fake();

        [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ] = $this->baseOrder();

        $firstItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 1,
            ]);

        OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'quantity' => 1,
            ]);

        $shipment = Shipment::factory()
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
                $firstItem,
                'orderItem',
            )
            ->create([
                'quantity' => 1,
            ]);

        $this->expectException(
            ValidationException::class,
        );

        try {
            $this
                ->provider(
                    staticAccessToken: 'token',
                )
                ->createShipment(
                    shipment: $shipment,

                    idempotencyKey: '11111111-2222-4333-8444-555555555555',
                );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_ambiguous_create_response_raises_review_required_exception_type(): void
    {
        Http::fake([
            'https://pathao.test/aladdin/api/v1/orders' => Http::response(
                [
                    'message' => 'Gateway timeout',
                ],
                504,
            ),
        ]);

        [
            'shipment' => $shipment,
        ] = $this->fullShipmentFixture();

        $this->expectException(
            ProviderSubmissionOutcomeUnknownException::class,
        );

        $this
            ->provider(
                staticAccessToken: 'token',
            )
            ->createShipment(
                shipment: $shipment,

                idempotencyKey: '11111111-2222-4333-8444-555555555555',
            );
    }

    public function test_shipping_method_pathao_metadata_cannot_override_connected_provider_configuration(): void
    {
        Http::fake([
            'https://pathao.test/aladdin/api/v1/orders' => Http::response(
                [
                    'data' => [
                        'consignment_id' => 'PATHAO-3001',
                    ],
                ],
                200,
            ),
        ]);

        [
            'shipment' => $shipment,

            'shippingMethod' => $shippingMethod,
        ] = $this->fullShipmentFixture();

        /*
         * Legacy checkout-level Pathao metadata must no
         * longer control the physical courier configuration.
         *
         * 8.8C separated:
         *
         * checkout shipping service
         *     from
         * physical courier provider.
         */
        $shippingMethod->update([
            'metadata' => [
                'pathao' => [
                    'store_id' => 999,

                    'item_weight_kg' => 1.5,

                    'delivery_type' => 12,

                    'item_type' => 1,

                    'special_instruction' => 'Legacy checkout override',
                ],
            ],
        ]);

        $shipment->unsetRelation(
            'shippingMethod',
        );

        /*
         * provider() is configured with:
         *
         * store_id                 = 12345
         * delivery_type            = 48
         * item_type                = 2
         * default_item_weight_kg   = 0.5
         */
        $this
            ->provider(
                staticAccessToken: 'token',
            )
            ->createShipment(
                shipment: $shipment,

                idempotencyKey: '11111111-2222-4333-8444-555555555555',
            );

        Http::assertSent(
            static function (Request $request): bool {
                if (
                    $request->url() !==
                    'https://pathao.test/aladdin/api/v1/orders'
                ) {
                    return false;
                }

                $data =
                    $request->data();

                return
                    ($data['store_id'] ?? null) ===
                    12345
                    &&
                    ($data['delivery_type'] ?? null) ===
                    48
                    &&
                    ($data['item_type'] ?? null) ===
                    2
                    &&
                    ($data['item_weight'] ?? null) ===
                    0.5
                    &&
                    ! array_key_exists(
                        'special_instruction',
                        $data,
                    );
            },
        );
    }

    /**
     * @return array{
     *     shipment: Shipment,
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function fullShipmentFixture(): array
    {
        [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ] = $this->baseOrder();

        $orderItem = OrderItem::factory()
            ->for(
                $order,
            )
            ->create([
                'product_name' => 'Test Product',

                'quantity' => 2,
            ]);

        $shipment = Shipment::factory()
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

        return [
            'shipment' => $shipment,

            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }

    /**
     * @return array{
     *     order: Order,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function baseOrder(): array
    {
        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => PathaoShippingProvider::CODE,

                    'is_active' => true,
                ]);

        $order = Order::factory()
            ->for(
                $user,
            )
            ->create([
                'status' => OrderStatus::Processing,

                'payment_method' => PaymentMethod::CashOnDelivery,

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

        return [
            'order' => $order,

            'shippingMethod' => $shippingMethod,
        ];
    }

    private function provider(
        ?string $staticAccessToken,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $username = null,
        ?string $password = null,
    ): PathaoShippingProvider {
        return new PathaoShippingProvider(
            baseUrl: 'https://pathao.test',

            staticAccessToken: $staticAccessToken,

            clientId: $clientId,

            clientSecret: $clientSecret,

            username: $username,

            password: $password,

            storeId: 12345,

            deliveryType: 48,

            itemType: 2,

            defaultItemWeightKg: 0.5,

            connectTimeoutSeconds: 2,

            timeoutSeconds: 5,
        );
    }
}
