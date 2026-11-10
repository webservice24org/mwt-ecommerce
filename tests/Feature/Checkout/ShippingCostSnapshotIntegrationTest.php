<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShippingCostSnapshotIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_cost_and_rate_snapshot_do_not_change_after_live_rate_is_edited(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'city' => 'Rangamati',

                'country_code' => 'BD',
            ]);

        /*
         * BDT 650.00
         */
        $product = Product::factory()
            ->create([
                'price' => 65000,

                'status' => 'published',
            ]);

        $method =
            ShippingMethod::factory()
                ->create([
                    'name' => 'Standard Delivery',

                    'code' => 'standard',

                    'is_active' => true,
                ]);

        $zone =
            ShippingZone::factory()
                ->create([
                    'name' => 'Out Of Dhaka',

                    'code' => 'out-of-dhaka',

                    'is_active' => true,

                    'priority' => 100,
                ]);

        /*
         * BDT 120.00
         */
        $rate =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'name' => 'Standard Delivery',

                    'code' => 'standard-bd',

                    'amount' => 12000,

                    'priority' => 100,

                    'is_active' => true,
                ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:'.$product->id => [
                        'product_id' => $product->id,

                        'variant_id' => null,

                        'quantity' => 1,
                    ],
                ],

                'storefront.checkout.address_id' => $address->id,

                'storefront.checkout.payment_method' => PaymentMethod::CashOnDelivery
                    ->value,
            ]);

        $idempotencyKey = app(
            CheckoutSessionService::class,
        )->orderSubmissionToken();

        $order = app(
            CreateOrderAction::class,
        )->execute(
            user: $user,

            shippingRateId: $rate->id,

            idempotencyKey: $idempotencyKey,
        );

        /*
         * ==================================================
         * ORIGINAL CHECKOUT SNAPSHOT
         * ==================================================
         */

        $this->assertSame(
            65000,
            $order->subtotal,
        );

        $this->assertSame(
            12000,
            $order->delivery_total,
        );

        $this->assertSame(
            77000,
            $order->grand_total,
        );

        $this->assertSame(
            $method->id,
            $order->shipping_method_id,
        );

        $this->assertSame(
            $rate->id,
            $order->shipping_rate_id,
        );

        $this->assertSame(
            'standard',
            $order->shipping_method_code,
        );

        $this->assertSame(
            'Standard Delivery',
            $order->shipping_method_name,
        );

        $this->assertSame(
            'standard-bd',
            $order->shipping_rate_code,
        );

        $this->assertSame(
            'Standard Delivery',
            $order->shipping_rate_name,
        );

        /*
         * ==================================================
         * ADMIN CHANGES LIVE SHIPPING CONFIGURATION
         * ==================================================
         *
         * Existing orders must NOT change.
         */

        $method->update([
            'name' => 'Changed Courier Method',

            'code' => 'changed-method',
        ]);

        $rate->update([
            'name' => 'Changed Shipping Rate',

            'code' => 'changed-rate',

            /*
             * BDT 999.00
             */
            'amount' => 99900,
        ]);

        $order = Order::query()
            ->findOrFail(
                $order->id,
            );

        /*
         * ==================================================
         * HISTORICAL ORDER MUST REMAIN IMMUTABLE
         * ==================================================
         */

        $this->assertSame(
            12000,
            $order->delivery_total,
        );

        $this->assertSame(
            77000,
            $order->grand_total,
        );

        $this->assertSame(
            'standard',
            $order->shipping_method_code,
        );

        $this->assertSame(
            'Standard Delivery',
            $order->shipping_method_name,
        );

        $this->assertSame(
            'standard-bd',
            $order->shipping_rate_code,
        );

        $this->assertSame(
            'Standard Delivery',
            $order->shipping_rate_name,
        );

        /*
         * The live configuration is allowed to change.
         * This proves the historical order is not reading
         * those values dynamically.
         */
        $this->assertSame(
            99900,
            $rate
                ->fresh()
                ?->amount,
        );

        $this->assertSame(
            'Changed Shipping Rate',
            $rate
                ->fresh()
                ?->name,
        );
    }
}
