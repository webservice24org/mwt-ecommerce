<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerAddressDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_delete_own_address(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $address,
                ),
            );

        $response->assertRedirect(
            route(
                'customer.addresses.index',
            ),
        );

        $this->assertDatabaseMissing(
            'addresses',
            [
                'id' => $address->id,
            ],
        );
    }

    public function test_customer_cannot_delete_another_customers_address(): void
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
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $address,
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $address->id,
                'user_id' => $otherCustomer->id,
            ],
        );
    }

    public function test_deleting_non_default_address_preserves_existing_default(): void
    {
        $user = User::factory()
            ->create();

        $defaultAddress =
            Address::factory()
                ->for($user)
                ->default()
                ->create();

        $address = Address::factory()
            ->for($user)
            ->create([
                'is_default' => false,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $address,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertDatabaseMissing(
            'addresses',
            [
                'id' => $address->id,
            ],
        );

        $this->assertTrue(
            $defaultAddress
                ->fresh()
                ->is_default,
        );
    }

    public function test_deleting_default_address_promotes_remaining_address(): void
    {
        $user = User::factory()
            ->create();

        $defaultAddress =
            Address::factory()
                ->for($user)
                ->default()
                ->create();

        $replacement =
            Address::factory()
                ->for($user)
                ->create([
                    'is_default' => false,
                ]);

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $defaultAddress,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertDatabaseMissing(
            'addresses',
            [
                'id' => $defaultAddress->id,
            ],
        );

        $this->assertTrue(
            $replacement
                ->fresh()
                ->is_default,
        );
    }

    public function test_deleting_default_address_uses_oldest_remaining_address_as_replacement(): void
    {
        $user = User::factory()
            ->create();

        $defaultAddress =
            Address::factory()
                ->for($user)
                ->default()
                ->create();

        $firstRemaining =
            Address::factory()
                ->for($user)
                ->create([
                    'is_default' => false,
                ]);

        $secondRemaining =
            Address::factory()
                ->for($user)
                ->create([
                    'is_default' => false,
                ]);

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $defaultAddress,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertTrue(
            $firstRemaining
                ->fresh()
                ->is_default,
        );

        $this->assertFalse(
            $secondRemaining
                ->fresh()
                ->is_default,
        );
    }

    public function test_customer_can_delete_only_remaining_address(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->default()
            ->create();

        $this
            ->actingAs(
                $user,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $address,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertDatabaseCount(
            'addresses',
            0,
        );
    }

    public function test_deleting_address_does_not_change_another_customers_default(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $defaultAddress =
            Address::factory()
                ->for($customer)
                ->default()
                ->create();

        $replacement =
            Address::factory()
                ->for($customer)
                ->create([
                    'is_default' => false,
                ]);

        $otherDefault =
            Address::factory()
                ->for($otherCustomer)
                ->default()
                ->create();

        $this
            ->actingAs(
                $customer,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $defaultAddress,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertTrue(
            $replacement
                ->fresh()
                ->is_default,
        );

        $this->assertTrue(
            $otherDefault
                ->fresh()
                ->is_default,
        );
    }
}
