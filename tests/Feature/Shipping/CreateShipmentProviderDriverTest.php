<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\CreateShipmentAction;
use App\Domain\Shipping\Data\CreateShipmentItemData;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CreateShipmentProviderDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_shipping_method_driver_no_longer_controls_physical_courier(): void
    {
        [
            'order' => $order,

            'orderItem' => $orderItem,

            'shippingMethod' => $shippingMethod,
        ] = $this->fixture(
            driver: 'not-registered',
        );

        $shipment =
            app(
                CreateShipmentAction::class,
            )->execute(
                order: $order,

                shippingMethod: $shippingMethod,

                items: [
                    new CreateShipmentItemData(
                        orderItemId: $orderItem->id,

                        quantity: 1,
                    ),
                ],

                providerCode: ShippingProviderCode::Manual,
            );

        $this->assertSame(
            $shippingMethod->id,
            $shipment
                ->shipping_method_id,
        );

        $this->assertSame(
            ShippingProviderCode::Manual,
            $shipment
                ->provider_code,
        );

        $this->assertNull(
            $shipment
                ->shipping_provider_account_id,
        );
    }

    public function test_connected_pathao_can_be_selected_independently_from_checkout_method_driver(): void
    {
        [
            'order' => $order,

            'orderItem' => $orderItem,

            'shippingMethod' => $shippingMethod,
        ] = $this->fixture(
            driver: 'manual',
        );

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        $manager =
            app(
                ShippingProviderAccountManager::class,
            );

        $account =
            $manager->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'Main Pathao',

                credentials: [
                    'client_id' => 'client',

                    'client_secret' => 'secret',
                ],

                settings: [
                    'store_id' => 123,
                ],

                actor: $admin,
            );

        $account =
            $manager->markConnected(
                $account,
                $admin,
            );

        $account =
            $manager
                ->updateOperationalSettings(
                    account: $account,

                    isActive: true,

                    autoSubmit: false,

                    isDefault: false,

                    actor: $admin,
                );

        $shipment =
            app(
                CreateShipmentAction::class,
            )->execute(
                order: $order,

                shippingMethod: $shippingMethod,

                items: [
                    new CreateShipmentItemData(
                        orderItemId: $orderItem->id,

                        quantity: 1,
                    ),
                ],

                providerCode: ShippingProviderCode::Pathao,
            );

        $this->assertSame(
            ShippingProviderCode::Pathao,
            $shipment
                ->provider_code,
        );

        $this->assertSame(
            $account->id,
            $shipment
                ->shipping_provider_account_id,
        );

        /*
         * Checkout service remains unchanged.
         */
        $this->assertSame(
            $shippingMethod->id,
            $shipment
                ->shipping_method_id,
        );
    }

    public function test_configured_but_unconnected_pathao_cannot_be_selected(): void
    {
        [
            'order' => $order,

            'orderItem' => $orderItem,

            'shippingMethod' => $shippingMethod,
        ] = $this->fixture(
            driver: 'manual',
        );

        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => true,
                ]);

        app(
            ShippingProviderAccountManager::class,
        )->configure(
            providerCode: ShippingProviderCode::Pathao,

            name: 'Pathao',

            credentials: [
                'client_id' => 'client',

                'client_secret' => 'secret',
            ],

            settings: [
                'store_id' => 123,
            ],

            actor: $admin,
        );

        try {
            app(
                CreateShipmentAction::class,
            )->execute(
                order: $order,

                shippingMethod: $shippingMethod,

                items: [
                    new CreateShipmentItemData(
                        orderItemId: $orderItem->id,

                        quantity: 1,
                    ),
                ],

                providerCode: ShippingProviderCode::Pathao,
            );

            $this->fail(
                'Unconnected Pathao must not be selectable.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'provider_code',
                $exception
                    ->errors(),
            );
        }

        $this->assertDatabaseMissing(
            'shipments',
            [
                'order_id' => $order->id,
            ],
        );
    }

    /**
     * @return array{
     *     order: Order,
     *     orderItem: OrderItem,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function fixture(
        string $driver,
    ): array {
        $user =
            User::factory()
                ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => $driver,

                    'is_active' => true,
                ]);

        $order =
            Order::factory()
                ->for(
                    $user,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'fulfilment_status' => FulfilmentStatus::Processing,

                    'shipping_method_id' => $shippingMethod->id,

                    'shipping_method_code' => $shippingMethod->code,

                    'shipping_method_name' => $shippingMethod->name,
                ]);

        $orderItem =
            OrderItem::factory()
                ->for(
                    $order,
                )
                ->create([
                    'quantity' => 1,
                ]);

        return [
            'order' => $order,

            'orderItem' => $orderItem,

            'shippingMethod' => $shippingMethod,
        ];
    }
}
