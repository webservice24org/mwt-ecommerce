<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerAddressCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_address_create_page(): void
    {
        $response = $this->get(
            route(
                'customer.addresses.create',
            ),
        );

        $response->assertRedirect(
            route(
                'login',
            ),
        );
    }

    public function test_unverified_customer_cannot_view_address_create_page(): void
    {
        $user = User::factory()
            ->unverified()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.addresses.create',
                ),
            );

        $response->assertRedirect(
            route(
                'verification.notice',
            ),
        );
    }

    public function test_verified_customer_can_view_address_create_page(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->get(
                route(
                    'customer.addresses.create',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Customer/Addresses/Create',
                    )
                    ->where(
                        'defaults.country_code',
                        'BD',
                    ),
            );
    }

    public function test_customer_can_create_delivery_address(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'customer.addresses.store',
                ),
                $this->validAddressData(),
            );

        $response->assertRedirect(
            route(
                'customer.addresses.index',
            ),
        );

        $this->assertDatabaseHas(
            'addresses',
            [
                'user_id' => $user->id,
                'label' => 'Home',
                'recipient_name' => 'Test Customer',
                'phone' => '+8801700000000',
                'city' => 'Dhaka',
                'country_code' => 'BD',
                'is_default' => true,
            ],
        );
    }

    public function test_first_address_becomes_default_automatically(): void
    {
        $user = User::factory()
            ->create();

        $data = $this
            ->validAddressData();

        $data['is_default'] = false;

        $this
            ->actingAs(
                $user,
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

        $address = $user
            ->addresses()
            ->firstOrFail();

        $this->assertTrue(
            $address->is_default,
        );
    }

    public function test_customer_cannot_assign_created_address_to_another_customer(): void
    {
        $user = User::factory()
            ->create();

        $otherCustomer = User::factory()
            ->create();

        $data = $this
            ->validAddressData();

        $data['user_id'] =
            $otherCustomer->id;

        $this
            ->actingAs(
                $user,
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
            $user->id,
            $address->user_id,
        );

        $this->assertNotSame(
            $otherCustomer->id,
            $address->user_id,
        );
    }

    public function test_new_default_address_replaces_existing_default(): void
    {
        $user = User::factory()
            ->create();

        $existingAddress =
            Address::factory()
                ->for($user)
                ->default()
                ->create();

        $data = $this
            ->validAddressData();

        $data['label'] = 'Office';
        $data['is_default'] = true;

        $this
            ->actingAs(
                $user,
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

        $this->assertFalse(
            $existingAddress
                ->fresh()
                ->is_default,
        );

        $this->assertDatabaseHas(
            'addresses',
            [
                'user_id' => $user->id,
                'label' => 'Office',
                'is_default' => true,
            ],
        );
    }

    public function test_address_requires_delivery_fields(): void
    {
        $user = User::factory()
            ->create();

        $response = $this
            ->actingAs(
                $user,
            )
            ->post(
                route(
                    'customer.addresses.store',
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

        $this->assertDatabaseCount(
            'addresses',
            0,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validAddressData(): array
    {
        return [
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
        ];
    }
}
