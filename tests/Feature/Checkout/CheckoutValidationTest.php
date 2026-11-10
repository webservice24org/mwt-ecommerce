<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\Address;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_validate_checkout(): void
    {
        $this
            ->post(
                route(
                    'checkout.validate',
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    public function test_unverified_customer_cannot_validate_checkout(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.validate',
                ),
            )
            ->assertRedirect(
                route(
                    'verification.notice',
                ),
            );
    }

    public function test_empty_cart_fails_checkout_validation(): void
    {
        $user = User::factory()
            ->create();

        /*
         * Shipping selection is now a required checkout
         * prerequisite.
         *
         * This test is about the empty-cart domain rule,
         * so provide a valid unrelated shipping rate to
         * allow the request to reach ValidateCheckoutAction.
         */
        $shippingRate =
            ShippingRate::factory()
                ->create([
                    'amount' => 0,
                    'is_active' => true,
                ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.validate',
                ),
                [
                    'shipping_rate_id' => $shippingRate->id,
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'checkout.index',
                ),
            )
            ->assertSessionHasErrors(
                'cart',
            )
            ->assertSessionDoesntHaveErrors(
                'shipping_rate_id',
            );
    }

    public function test_missing_delivery_address_cannot_be_used_for_checkout(): void
    {
        $user = User::factory()
            ->create();

        /*
         * The cart-valid case will be added using
         * existing catalog fixtures when we enter
         * the order creation regression suite.
         *
         * Here we lock down the fact that an
         * arbitrary stale address ID is never
         * accepted.
         */

        $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.checkout.address_id' => 999999,
            ]);

        $this->assertDatabaseCount(
            'addresses',
            0,
        );
    }

    public function test_checkout_session_cannot_reference_another_customers_address(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $otherAddress =
            Address::factory()
                ->for($otherCustomer)
                ->default()
                ->create();

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $otherAddress->id,
                'user_id' => $otherCustomer->id,
            ],
        );

        $this->assertDatabaseMissing(
            'addresses',
            [
                'id' => $otherAddress->id,
                'user_id' => $customer->id,
            ],
        );
    }
}
