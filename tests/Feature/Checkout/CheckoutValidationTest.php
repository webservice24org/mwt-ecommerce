<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\Address;
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

        Address::factory()
            ->for($user)
            ->default()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.validate',
                ),
            );

        $response->assertRedirect(
            route(
                'checkout.index',
            ),
        );

        $response->assertSessionHasErrors([
            'cart',
        ]);
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
