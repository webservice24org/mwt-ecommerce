<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this
            ->get(
                route(
                    'checkout.index',
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    public function test_unverified_customer_cannot_access_checkout(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'checkout.index',
                ),
            )
            ->assertRedirect(
                route(
                    'verification.notice',
                ),
            );
    }

    public function test_customer_with_empty_cart_is_redirected_to_cart(): void
    {
        $user = User::factory()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'checkout.index',
                ),
            )
            ->assertRedirect(
                route(
                    'cart.show',
                ),
            );
    }

    public function test_stale_cart_item_is_removed_and_customer_is_redirected_to_cart(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->withSession([
                'storefront.cart' => [
                    'product:999999' => [
                        'product_id' => 999999,
                        'variant_id' => null,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->get(
                route(
                    'checkout.index',
                ),
            );

        $response->assertRedirect(
            route(
                'cart.show',
            ),
        );

        $this->assertSame(
            [],
            session(
                'storefront.cart',
                [],
            ),
        );
    }
}
