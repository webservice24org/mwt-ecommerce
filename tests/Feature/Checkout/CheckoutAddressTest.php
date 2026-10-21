<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Checkout\Services\CheckoutSessionService;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CheckoutAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_select_owned_address_for_checkout(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.address.select',
                ),
                [
                    'address_id' => $address->id,
                ],
            )
            ->assertRedirect(
                route(
                    'checkout.index',
                ),
            );

        $this->assertSame(
            $address->id,
            session(
                'storefront.checkout.address_id',
            ),
        );
    }

    public function test_customer_cannot_select_another_customers_address(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($otherCustomer)
            ->create();

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'checkout.address.select',
                ),
                [
                    'address_id' => $address->id,
                ],
            )
            ->assertNotFound();

        $this->assertNull(
            session(
                'storefront.checkout.address_id',
            ),
        );
    }

    public function test_checkout_address_selection_requires_address_id(): void
    {
        $user = User::factory()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.address.select',
                ),
                [],
            )
            ->assertSessionHasErrors([
                'address_id',
            ]);
    }

    public function test_customer_can_create_address_from_checkout(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.addresses.store',
                ),
                $this->validAddressData(),
            );

        $response->assertRedirect(
            route(
                'checkout.index',
            ),
        );

        $address = Address::query()
            ->where(
                'user_id',
                $user->id,
            )
            ->firstOrFail();

        $this->assertSame(
            $address->id,
            session(
                'storefront.checkout.address_id',
            ),
        );

        $this->assertSame(
            $user->id,
            $address->user_id,
        );
    }

    public function test_first_checkout_created_address_becomes_default(): void
    {
        $user = User::factory()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'checkout.addresses.store',
                ),
                $this->validAddressData([
                    'is_default' => false,
                ]),
            )
            ->assertRedirect(
                route(
                    'checkout.index',
                ),
            );

        $address = $user
            ->addresses()
            ->firstOrFail();

        $this->assertTrue(
            $address->is_default,
        );
    }

    public function test_checkout_created_address_cannot_be_assigned_to_another_customer(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $data =
            $this->validAddressData();

        $data['user_id'] =
            $otherCustomer->id;

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'checkout.addresses.store',
                ),
                $data,
            )
            ->assertRedirect(
                route(
                    'checkout.index',
                ),
            );

        $address = Address::query()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $customer->id,
            $address->user_id,
        );

        $this->assertNotSame(
            $otherCustomer->id,
            $address->user_id,
        );
    }

    public function test_guest_cannot_select_checkout_address(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create();

        $this
            ->post(
                route(
                    'checkout.address.select',
                ),
                [
                    'address_id' => $address->id,
                ],
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    public function test_guest_cannot_create_checkout_address(): void
    {
        $this
            ->post(
                route(
                    'checkout.addresses.store',
                ),
                $this->validAddressData(),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );

        $this->assertDatabaseCount(
            'addresses',
            0,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validAddressData(
        array $overrides = [],
    ): array {
        return array_merge(
            [
                'label' => 'Home',
                'recipient_name' => 'Checkout Customer',
                'phone' => '+8801700000000',
                'address_line_1' => '123 Checkout Road',
                'address_line_2' => null,
                'city' => 'Dhaka',
                'state_or_region' => 'Dhaka',
                'postal_code' => '1200',
                'country_code' => 'BD',
                'is_default' => false,
            ],
            $overrides,
        );
    }

    public function test_checkout_session_cannot_resolve_another_customers_address(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $customerDefault =
            Address::factory()
                ->for($customer)
                ->default()
                ->create();

        $otherAddress =
            Address::factory()
                ->for($otherCustomer)
                ->create();

        $service = app(
            CheckoutSessionService::class,
        );

        $this
            ->actingAs(
                $customer,
            )
            ->withSession([
                'storefront.checkout.address_id' => $otherAddress->id,
            ]);

        $resolved =
            $service->resolveAddress(
                $customer,
            );

        $this->assertNotNull(
            $resolved,
        );

        $this->assertSame(
            $customerDefault->id,
            $resolved->id,
        );

        $this->assertSame(
            $customerDefault->id,
            session(
                'storefront.checkout.address_id',
            ),
        );
    }
}
