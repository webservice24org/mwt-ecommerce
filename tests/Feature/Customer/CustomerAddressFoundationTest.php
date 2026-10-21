<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerAddressFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_belongs_to_customer(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create();

        $this->assertTrue(
            $address->user->is(
                $user,
            ),
        );
    }

    public function test_customer_has_many_addresses(): void
    {
        $user = User::factory()
            ->create();

        Address::factory()
            ->count(2)
            ->for($user)
            ->create();

        $this->assertCount(
            2,
            $user->addresses,
        );
    }

    public function test_address_defaults_to_non_default(): void
    {
        $address = Address::factory()
            ->create();

        $this->assertFalse(
            $address->is_default,
        );
    }

    public function test_address_default_state_is_cast_to_boolean(): void
    {
        $address = Address::factory()
            ->default()
            ->create();

        $this->assertTrue(
            $address->is_default,
        );
    }

    public function test_deleting_customer_deletes_owned_addresses(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create();

        $user->delete();

        $this->assertDatabaseMissing(
            'addresses',
            [
                'id' => $address->id,
            ],
        );
    }

    public function test_different_customers_keep_separate_addresses(): void
    {
        $firstCustomer = User::factory()
            ->create();

        $secondCustomer = User::factory()
            ->create();

        $firstAddress = Address::factory()
            ->for($firstCustomer)
            ->create();

        $secondAddress = Address::factory()
            ->for($secondCustomer)
            ->create();

        $this->assertTrue(
            $firstCustomer
                ->addresses()
                ->whereKey(
                    $firstAddress->id,
                )
                ->exists(),
        );

        $this->assertFalse(
            $firstCustomer
                ->addresses()
                ->whereKey(
                    $secondAddress->id,
                )
                ->exists(),
        );
    }

    public function test_dashboard_shows_authenticated_customers_address_count(): void
    {
        $user = User::factory()
            ->create();

        Address::factory()
            ->count(3)
            ->for($user)
            ->create();

        Address::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.dashboard',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->where(
                        'summary.addresses',
                        3,
                    ),
            );
    }
}
