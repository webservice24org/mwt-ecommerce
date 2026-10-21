<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerAddressSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_address_index(): void
    {
        $this
            ->get(
                route(
                    'customer.addresses.index',
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    public function test_guest_cannot_view_address_create_page(): void
    {
        $this
            ->get(
                route(
                    'customer.addresses.create',
                ),
            )
            ->assertRedirect(
                route(
                    'login',
                ),
            );
    }

    public function test_guest_cannot_create_address(): void
    {
        $this
            ->post(
                route(
                    'customer.addresses.store',
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

    public function test_unverified_customer_cannot_access_address_index(): void
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
                    'customer.addresses.index',
                ),
            )
            ->assertRedirect(
                route(
                    'verification.notice',
                ),
            );
    }

    public function test_unverified_customer_cannot_create_address(): void
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
                    'customer.addresses.store',
                ),
                $this->validAddressData(),
            )
            ->assertRedirect(
                route(
                    'verification.notice',
                ),
            );

        $this->assertDatabaseCount(
            'addresses',
            0,
        );
    }

    public function test_address_index_only_contains_authenticated_customers_addresses(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $ownedAddress = Address::factory()
            ->for($customer)
            ->create([
                'label' => 'My Home',
            ]);

        Address::factory()
            ->for($otherCustomer)
            ->create([
                'label' => 'Other Home',
            ]);

        $response = $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.addresses.index',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Addresses/Index',
                    )
                    ->has(
                        'addresses',
                        1,
                    )
                    ->where(
                        'addresses.0.id',
                        $ownedAddress->id,
                    )
                    ->where(
                        'addresses.0.label',
                        'My Home',
                    ),
            );
    }

    public function test_customer_cannot_view_another_customers_address_edit_page(): void
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
            ->get(
                route(
                    'customer.addresses.edit',
                    $address,
                ),
            )
            ->assertNotFound();
    }

    public function test_customer_cannot_update_another_customers_address(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($otherCustomer)
            ->create([
                'recipient_name' => 'Original Recipient',
            ]);

        $this
            ->actingAs(
                $customer,
            )
            ->put(
                route(
                    'customer.addresses.update',
                    $address,
                ),
                $this->validAddressData([
                    'recipient_name' => 'Unauthorized Recipient',
                ]),
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $address->id,
                'user_id' => $otherCustomer->id,
                'recipient_name' => 'Original Recipient',
            ],
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

    public function test_customer_cannot_delete_another_customers_address(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $address = Address::factory()
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
                    $address,
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $address->id,
                'user_id' => $otherCustomer->id,
                'is_default' => true,
            ],
        );
    }

    public function test_customer_cannot_change_address_owner_when_creating(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $data = $this
            ->validAddressData();

        $data['user_id'] =
            $otherCustomer->id;

        $this
            ->actingAs(
                $customer,
            )
            ->post(
                route(
                    'customer.addresses.store',
                ),
                $data,
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
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

    public function test_customer_cannot_change_address_owner_when_updating(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $address = Address::factory()
            ->for($customer)
            ->create();

        $data = $this
            ->validAddressData();

        $data['user_id'] =
            $otherCustomer->id;

        $this
            ->actingAs(
                $customer,
            )
            ->put(
                route(
                    'customer.addresses.update',
                    $address,
                ),
                $data,
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $address->id,
                'user_id' => $customer->id,
            ],
        );
    }

    public function test_missing_address_returns_not_found(): void
    {
        $customer = User::factory()
            ->create();

        $missingId = 999999;

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                route(
                    'customer.addresses.edit',
                    $missingId,
                ),
            )
            ->assertNotFound();

        $this
            ->actingAs(
                $customer,
            )
            ->put(
                route(
                    'customer.addresses.update',
                    $missingId,
                ),
                $this->validAddressData(),
            )
            ->assertNotFound();

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                route(
                    'customer.addresses.default',
                    $missingId,
                ),
            )
            ->assertNotFound();

        $this
            ->actingAs(
                $customer,
            )
            ->delete(
                route(
                    'customer.addresses.destroy',
                    $missingId,
                ),
            )
            ->assertNotFound();
    }

    public function test_non_numeric_address_identifier_returns_not_found(): void
    {
        $customer = User::factory()
            ->create();

        $this
            ->actingAs(
                $customer,
            )
            ->get(
                '/account/addresses/not-an-id/edit',
            )
            ->assertNotFound();

        $this
            ->actingAs(
                $customer,
            )
            ->put(
                '/account/addresses/not-an-id',
                $this->validAddressData(),
            )
            ->assertNotFound();

        $this
            ->actingAs(
                $customer,
            )
            ->patch(
                '/account/addresses/not-an-id/default',
            )
            ->assertNotFound();

        $this
            ->actingAs(
                $customer,
            )
            ->delete(
                '/account/addresses/not-an-id',
            )
            ->assertNotFound();
    }

    public function test_default_address_operations_never_affect_another_customer(): void
    {
        $customer = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $customerOldDefault =
            Address::factory()
                ->for($customer)
                ->default()
                ->create();

        $customerNewDefault =
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
                    $customerNewDefault,
                ),
            )
            ->assertRedirect(
                route(
                    'customer.addresses.index',
                ),
            );

        $this->assertFalse(
            $customerOldDefault
                ->fresh()
                ->is_default,
        );

        $this->assertTrue(
            $customerNewDefault
                ->fresh()
                ->is_default,
        );

        $this->assertTrue(
            $otherDefault
                ->fresh()
                ->is_default,
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
                'recipient_name' => 'Test Customer',
                'phone' => '+8801700000000',
                'address_line_1' => '123 Test Road',
                'address_line_2' => 'Apartment 4B',
                'city' => 'Dhaka',
                'state_or_region' => 'Dhaka',
                'postal_code' => '1200',
                'country_code' => 'BD',
                'is_default' => false,
            ],
            $overrides,
        );
    }
}
