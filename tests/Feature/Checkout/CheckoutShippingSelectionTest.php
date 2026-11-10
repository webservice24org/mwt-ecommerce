<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Actions\ValidateCheckoutAction;
use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Domain\Orders\Actions\CreateOrderAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CheckoutShippingSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_shipping_rate_becomes_authoritative_delivery_total(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'city' => 'Dhaka',
                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'price' => 1000,
                'status' => 'published',
            ]);

        $rate = ShippingRate::factory()
            ->create([
                'amount' => 80,
            ]);

        $this
            ->actingAs($user)
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

        $checkout = app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,
            shippingRateId: $rate->id,
        );

        $this->assertSame(
            80,
            $checkout
                ->totals
                ->deliveryTotal,
        );

        $this->assertSame(
            1080,
            $checkout
                ->totals
                ->grandTotal,
        );

        $this->assertSame(
            $rate->id,
            $checkout
                ->shippingQuote
                ->shippingRateId,
        );
    }

    public function test_free_shipping_threshold_produces_zero_delivery_total(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'city' => 'Dhaka',
                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'price' => 2000,
                'status' => 'published',
            ]);

        $rate = ShippingRate::factory()
            ->create([
                'amount' => 80,

                'free_shipping_threshold' => 1500,
            ]);

        $this
            ->actingAs($user)
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

        $checkout = app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,
            shippingRateId: $rate->id,
        );

        $this->assertSame(
            0,
            $checkout
                ->totals
                ->deliveryTotal,
        );

        $this->assertSame(
            2000,
            $checkout
                ->totals
                ->grandTotal,
        );

        $this->assertTrue(
            $checkout
                ->shippingQuote
                ->free,
        );
    }

    public function test_manually_selected_zone_rate_does_not_require_address_matching_rules(): void
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

        $product = Product::factory()
            ->create([
                'price' => 65000,
                'status' => 'published',
            ]);

        $zone = ShippingZone::factory()
            ->create([
                'name' => 'Out Of Dhaka',
                'code' => 'out-of-dhaka',
                'is_active' => true,
            ]);

        /*
         * Deliberately create NO matching rules.
         *
         * Manual checkout zone selection must still work.
         */

        $rate = ShippingRate::factory()
            ->create([
                'name' => 'Standard Delivery',
                'code' => 'standard-bd',
                'amount' => 12000,
                'is_active' => true,
            ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

        $this
            ->actingAs($user)
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

        $checkout = app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,

            shippingRateId: $rate->id,
        );

        $this->assertSame(
            12000,
            $checkout
                ->totals
                ->deliveryTotal,
        );

        $this->assertSame(
            77000,
            $checkout
                ->totals
                ->grandTotal,
        );

        $this->assertSame(
            $rate->id,
            $checkout
                ->shippingQuote
                ->shippingRateId,
        );
    }

    public function test_inactive_shipping_method_is_rejected(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $product = Product::factory()
            ->create([
                'price' => 1000,
                'status' => 'published',
            ]);

        $method =
            ShippingMethod::factory()
                ->create([
                    'is_active' => false,
                ]);

        $rate =
            ShippingRate::factory()
                ->for($method)
                ->create();

        $this
            ->actingAs($user)
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

        $this->expectException(
            ValidationException::class,
        );

        app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,
            shippingRateId: $rate->id,
        );
    }

    public function test_order_snapshots_selected_shipping_method_and_rate(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create([
                'city' => 'Dhaka',
                'country_code' => 'BD',
            ]);

        $product = Product::factory()
            ->create([
                'price' => 1000,
                'status' => 'published',
            ]);

        $method =
            ShippingMethod::factory()
                ->create([
                    'name' => 'Standard Delivery',

                    'code' => 'standard',
                ]);

        $rate =
            ShippingRate::factory()
                ->for($method)
                ->create([
                    'name' => 'Dhaka Standard',

                    'code' => 'dhaka-standard',

                    'amount' => 80,
                ]);

        $this
            ->actingAs($user)
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

        $this->assertSame(
            $method->id,
            $order
                ->shipping_method_id,
        );

        $this->assertSame(
            $rate->id,
            $order
                ->shipping_rate_id,
        );

        $this->assertSame(
            'standard',
            $order
                ->shipping_method_code,
        );

        $this->assertSame(
            'Standard Delivery',
            $order
                ->shipping_method_name,
        );

        $this->assertSame(
            'dhaka-standard',
            $order
                ->shipping_rate_code,
        );

        $this->assertSame(
            'Dhaka Standard',
            $order
                ->shipping_rate_name,
        );

        $this->assertSame(
            80,
            $order
                ->delivery_total,
        );

        $this->assertSame(
            1080,
            $order
                ->grand_total,
        );
    }

    public function test_checkout_endpoint_requires_shipping_rate(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route(
                    'checkout.validate',
                ),
                [],
            );

        $response
            ->assertSessionHasErrors(
                'shipping_rate_id',
            );
    }
}
