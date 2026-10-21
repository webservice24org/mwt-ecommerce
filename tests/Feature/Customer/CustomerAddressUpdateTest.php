<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerAddressUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_own_address_edit_page(): void
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
            ->get(
                route(
                    'customer.addresses.edit',
                    $address,
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Addresses/Edit',
                    )
                    ->where(
                        'address.id',
                        $address->id,
                    )
                    ->where(
                        'address.recipient_name',
                        $address->recipient_name,
                    ),
            );
    }

    public function test_customer_can_update_own_address(): void
    {
        $user = User::factory()
            ->create();

        $address = Address::factory()
            ->for($user)
            ->create([
                'is_default' => true,
            ]);

        $response = $this
            ->actingAs(
                $user,
            )
            ->put(
                route(
                    'customer.addresses.update',
                    $address,
                ),
                $this->validAddressData([
                    'label' => 'Updated Home',
                    'recipient_name' => 'Updated Customer',
                    'city' => 'Chattogram',
                    'is_default' => true,
                ]),
            );

        $response->assertRedirect(
            route(
                'customer.addresses.index',
            ),
        );

        $this->assertDatabaseHas(
            'addresses',
            [
                'id' => $address->id,
                'user_id' => $user->id,
                'label' => 'Updated Home',
                'recipient_name' => 'Updated Customer',
                'city' => 'Chattogram',
                'is_default' => true,
            ],
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
                    'recipient_name' => 'Unauthorized Change',
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

    public function test_customer_cannot_change_address_owner_during_update(): void
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

    public function test_current_default_cannot_be_unset_without_replacement(): void
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
            ->put(
                route(
                    'customer.addresses.update',
                    $address,
                ),
                $this->validAddressData([
                    'is_default' => false,
                ]),
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
    }

    public function test_address_update_requires_delivery_fields(): void
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
            ->put(
                route(
                    'customer.addresses.update',
                    $address,
                ),
                [
                    'is_default' => false,
                ],
            );

        $response->assertSessionHasErrors([
            'recipient_name',
            'phone',
            'address_line_1',
            'city',
            'country_code',
        ]);
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
