<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Actions\ValidateCheckoutAction;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Models\Address;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ManualShippingZoneHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_exposes_only_active_zones_with_preferred_eligible_rate(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout(
            productPrice: 65000,
        );

        $method =
            ShippingMethod::factory()
                ->create([
                    'is_active' => true,
                ]);

        $dhaka =
            ShippingZone::factory()
                ->create([
                    'name' => 'Dhaka',

                    'code' => 'dhaka',

                    'is_active' => true,

                    'priority' => 10,
                ]);

        /*
         * Preferred rate.
         */
        $preferred =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'name' => 'Dhaka Standard',

                    'code' => 'dhaka-standard',

                    'amount' => 8000,

                    'priority' => 10,

                    'is_active' => true,
                ]);

        $preferred
            ->zones()
            ->attach(
                $dhaka,
            );

        /*
         * Also eligible, but lower preference.
         */
        $secondary =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'name' => 'Dhaka Secondary',

                    'code' => 'dhaka-secondary',

                    'amount' => 7000,

                    'priority' => 20,

                    'is_active' => true,
                ]);

        $secondary
            ->zones()
            ->attach(
                $dhaka,
            );

        /*
         * Higher priority, but inactive.
         */
        $inactiveRate =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'code' => 'inactive',

                    'priority' => 1,

                    'is_active' => false,
                ]);

        $inactiveRate
            ->zones()
            ->attach(
                $dhaka,
            );

        /*
         * Active, but subtotal threshold makes it
         * unavailable.
         */
        $thresholdBlocked =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'code' => 'threshold-blocked',

                    'priority' => 2,

                    'is_active' => true,

                    'min_order_amount' => 100000,
                ]);

        $thresholdBlocked
            ->zones()
            ->attach(
                $dhaka,
            );

        /*
         * Inactive zone must disappear completely.
         */
        $inactiveZone =
            ShippingZone::factory()
                ->create([
                    'name' => 'Inactive Zone',

                    'code' => 'inactive-zone',

                    'is_active' => false,

                    'priority' => 1,
                ]);

        $inactiveZoneRate =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'code' => 'inactive-zone-rate',

                    'amount' => 1000,

                    'priority' => 1,

                    'is_active' => true,
                ]);

        $inactiveZoneRate
            ->zones()
            ->attach(
                $inactiveZone,
            );

        /*
         * Global rate must not appear in manual
         * shipping-zone dropdown.
         */
        ShippingRate::factory()
            ->for(
                $method,
            )
            ->create([
                'code' => 'hidden-global',

                'amount' => 100,

                'priority' => 0,

                'is_active' => true,
            ]);

        $response =
            $this
                ->actingAs(
                    $user,
                )
                ->get(
                    route(
                        'checkout.index',
                    ),
                );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Frontend/Checkout/Index',
                    )
                    ->has(
                        'shippingZones',
                        1,
                    )
                    ->where(
                        'shippingZones.0.zone_name',
                        'Dhaka',
                    )
                    ->where(
                        'shippingZones.0.shipping_rate_id',
                        $preferred->id,
                    )
                    ->where(
                        'shippingZones.0.amount',
                        8000,
                    ),
            );
    }

    public function test_hidden_secondary_rate_cannot_be_submitted_manually(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $zone =
            ShippingZone::factory()
                ->create([
                    'is_active' => true,
                ]);

        $method =
            ShippingMethod::factory()
                ->create([
                    'is_active' => true,
                ]);

        $preferred =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'priority' => 10,

                    'is_active' => true,
                ]);

        $preferred
            ->zones()
            ->attach(
                $zone,
            );

        $hidden =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'priority' => 20,

                    'is_active' => true,
                ]);

        $hidden
            ->zones()
            ->attach(
                $zone,
            );

        $this->expectException(
            ValidationException::class,
        );

        app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,

            shippingRateId: $hidden->id,
        );
    }

    public function test_rate_attached_only_to_inactive_zone_is_rejected(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $zone =
            ShippingZone::factory()
                ->create([
                    'is_active' => false,
                ]);

        $rate =
            ShippingRate::factory()
                ->create([
                    'is_active' => true,
                ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

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

    public function test_hidden_global_rate_is_rejected_after_manual_zones_are_configured(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout();

        $method =
            ShippingMethod::factory()
                ->create([
                    'is_active' => true,
                ]);

        $zone =
            ShippingZone::factory()
                ->create([
                    'is_active' => true,
                ]);

        $zonedRate =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'is_active' => true,
                ]);

        $zonedRate
            ->zones()
            ->attach(
                $zone,
            );

        /*
         * This rate is not displayed in the manual
         * zone dropdown.
         */
        $globalRate =
            ShippingRate::factory()
                ->for(
                    $method,
                )
                ->create([
                    'code' => 'hidden-global',

                    'is_active' => true,
                ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,

            shippingRateId: $globalRate->id,
        );
    }

    public function test_free_shipping_threshold_remains_server_authoritative(): void
    {
        [
            'user' => $user,
        ] = $this->prepareCheckout(
            productPrice: 200000,
        );

        $zone =
            ShippingZone::factory()
                ->create([
                    'is_active' => true,
                ]);

        $rate =
            ShippingRate::factory()
                ->create([
                    'amount' => 12000,

                    'free_shipping_threshold' => 150000,

                    'is_active' => true,
                ]);

        $rate
            ->zones()
            ->attach(
                $zone,
            );

        $checkout = app(
            ValidateCheckoutAction::class,
        )->execute(
            user: $user,

            shippingRateId: $rate->id,
        );

        $this->assertTrue(
            $checkout
                ->shippingQuote
                ->free,
        );

        $this->assertSame(
            0,
            $checkout
                ->totals
                ->deliveryTotal,
        );

        $this->assertSame(
            200000,
            $checkout
                ->totals
                ->grandTotal,
        );
    }

    /**
     * @return array{
     *     user: User,
     *     address: Address,
     *     product: Product
     * }
     */
    private function prepareCheckout(
        int $productPrice = 65000,
    ): array {
        $user =
            User::factory()
                ->create();

        $address =
            Address::factory()
                ->for(
                    $user,
                )
                ->default()
                ->create([
                    'city' => 'Rangamati',

                    'country_code' => 'BD',
                ]);

        $product =
            Product::factory()
                ->create([
                    'price' => $productPrice,

                    'status' => 'published',
                ]);

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

        return [
            'user' => $user,

            'address' => $address,

            'product' => $product,
        ];
    }
}
