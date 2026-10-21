<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerAddressDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_make_owned_address_default(): void
    {
        $user = User::factory()
            ->create();

        $currentDefault =
            Address::factory()
                ->for($user)
                ->default()
                ->create();

        $newDefault =
            Address::factory()
                ->for($user)
                ->create([
                    'is_default' => false,
                ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->patch(
                route(
                    'customer.addresses.default',
                    $newDefault,
                ),
            );

        $response->assertRedirect(
            route(
                'customer.addresses.index',
            ),
        );

        $this->assertFalse(
            $currentDefault
                ->fresh()
                ->is_default,
        );

        $this->assertTrue(
            $newDefault
                ->fresh()
                ->is_default,
        );
    }

    public function test_setting_existing_default_again_is_idempotent(): void
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
            ->patch(
                route(
                    'customer.addresses.default',
                    $address,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertTrue(
            $address
                ->fresh()
                ->is_default,
        );

        $this->assertSame(
            1,
            $user
                ->addresses()
                ->where(
                    'is_default',
                    true,
                )
                ->count(),
        );
    }

    public function test_customer_cannot_make_another_customers_address_default(): void
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
                ->create([
                    'is_default' => false,
                ]);

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'customer.addresses.default',
                    $otherAddress,
                ),
            )
            ->assertNotFound();

        $this->assertTrue(
            $customerDefault
                ->fresh()
                ->is_default,
        );

        $this->assertFalse(
            $otherAddress
                ->fresh()
                ->is_default,
        );
    }

    public function test_setting_default_does_not_change_other_customers_default(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $oldDefault =
            Address::factory()
                ->for($customer)
                ->default()
                ->create();

        $newDefault =
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
            ->patch(
                route(
                    'customer.addresses.default',
                    $newDefault,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertFalse(
            $oldDefault
                ->fresh()
                ->is_default,
        );

        $this->assertTrue(
            $newDefault
                ->fresh()
                ->is_default,
        );

        $this->assertTrue(
            $otherDefault
                ->fresh()
                ->is_default,
        );
    }

    public function test_customer_has_only_one_default_after_switching(): void
    {
        $user = User::factory()
            ->create();

        Address::factory()
            ->for($user)
            ->default()
            ->create();

        $newDefault =
            Address::factory()
                ->for($user)
                ->create([
                    'is_default' => false,
                ]);

        Address::factory()
            ->for($user)
            ->create([
                'is_default' => false,
            ]);

        $this
            ->actingAs(
                $user,
            )
            ->patch(
                route(
                    'customer.addresses.default',
                    $newDefault,
                ),
            );

        $this->assertSame(
            1,
            $user
                ->addresses()
                ->where(
                    'is_default',
                    true,
                )
                ->count(),
        );

        $this->assertTrue(
            $newDefault
                ->fresh()
                ->is_default,
        );
    }
}
