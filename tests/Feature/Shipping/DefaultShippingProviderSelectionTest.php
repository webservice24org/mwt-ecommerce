<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Queries\GetAdminOrderShippingReadModelQuery;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DefaultShippingProviderSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_is_default_when_no_operational_default_courier_exists(): void
    {
        $order =
            $this->order();

        $shipping =
            app(
                GetAdminOrderShippingReadModelQuery::class,
            )->get(
                $order,
            );

        $this->assertSame(
            ShippingProviderCode::Manual->value,
            $shipping['default_provider_code'],
        );

        $manual =
            collect(
                $shipping['provider_options'],
            )->firstWhere(
                'code',
                ShippingProviderCode::Manual->value,
            );

        $this->assertIsArray(
            $manual,
        );

        $this->assertTrue(
            $manual['selectable'],
        );
    }

    public function test_operational_default_pathao_account_becomes_default_provider(): void
    {
        $admin =
            Admin::factory()
                ->create();

        $manager =
            app(
                ShippingProviderAccountManager::class,
            );

        $account =
            $manager->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'Main Pathao',

                credentials: [
                    /*
                     * Static access token is sufficient for
                     * constructing the runtime provider during
                     * this read-model test.
                     */
                    'access_token' => 'TEST-PATHAO-ACCESS-TOKEN',
                ],

                settings: [
                    'store_id' => 12345,

                    'delivery_type' => 48,

                    'item_type' => 2,

                    'default_item_weight_kg' => 0.5,
                ],

                actor: $admin,
            );

        $account =
            $manager->markConnected(
                $account,
                $admin,
            );

        $manager->updateOperationalSettings(
            account: $account,

            isActive: true,

            autoSubmit: false,

            isDefault: true,

            actor: $admin,
        );

        /*
         * Make sure the registry is rebuilt from the newly
         * operational DB-backed provider account.
         */
        app()->forgetInstance(
            ShippingProviderRegistry::class,
        );

        $shipping =
            app(
                GetAdminOrderShippingReadModelQuery::class,
            )->get(
                $this->order(),
            );

        $this->assertSame(
            ShippingProviderCode::Pathao->value,
            $shipping['default_provider_code'],
        );

        $pathao =
            collect(
                $shipping['provider_options'],
            )->firstWhere(
                'code',
                ShippingProviderCode::Pathao->value,
            );

        $this->assertIsArray(
            $pathao,
        );

        $this->assertTrue(
            $pathao['selectable'],
        );

        $this->assertTrue(
            $pathao['connected'],
        );

        $this->assertTrue(
            $pathao['is_default'],
        );

        $this->assertSame(
            'Main Pathao',
            $pathao['account_name'],
        );
    }

    private function order(): Order
    {
        $user =
            User::factory()
                ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    /*
                     * Checkout delivery service remains
                     * independent from the physical courier.
                     */
                    'driver' => 'manual',

                    'is_active' => true,
                ]);

        return Order::factory()
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
    }
}
