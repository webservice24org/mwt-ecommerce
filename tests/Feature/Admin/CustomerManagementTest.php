<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\Address;
use App\Models\Admin;
use App\Models\CustomerAccountAudit;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customer_index(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        User::factory()
            ->count(2)
            ->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Customers/Index',
                    )
                    ->has(
                        'customers.data',
                        2,
                    ),
            );
    }

    public function test_manager_can_view_customer_index(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Manager,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                ),
            )
            ->assertOk();
    }

    public function test_editor_cannot_view_customer_index(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Editor,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                ),
            )
            ->assertForbidden();
    }

    public function test_guest_cannot_view_customer_index(): void
    {
        $this
            ->get(
                route(
                    'admin.customers.index',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_customer_index_can_search_by_name(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        User::factory()->create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
        ]);

        User::factory()->create([
            'name' => 'John Shopper',
            'email' => 'john@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                    [
                        'search' => 'Jane',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'customers.data',
                        1,
                    )
                    ->where(
                        'customers.data.0.name',
                        'Jane Customer',
                    ),
            );
    }

    public function test_customer_index_can_search_by_email(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        User::factory()->create([
            'name' => 'First Customer',
            'email' => 'first@example.com',
        ]);

        User::factory()->create([
            'name' => 'Second Customer',
            'email' => 'second@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                    [
                        'search' => 'second@example.com',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'customers.data',
                        1,
                    )
                    ->where(
                        'customers.data.0.email',
                        'second@example.com',
                    ),
            );
    }

    public function test_customer_index_can_filter_verification_status(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                    [
                        'verified' => '1',
                    ],
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'customers.data',
                        1,
                    )
                    ->where(
                        'customers.data.0.email',
                        'verified@example.com',
                    )
                    ->where(
                        'customers.data.0.verified',
                        true,
                    ),
            );
    }

    public function test_customer_index_includes_order_count(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer->id,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'customers.data.0.orders_count',
                        2,
                    ),
            );
    }

    public function test_customer_index_does_not_expose_authentication_secrets(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        User::factory()->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.index',
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->missing(
                        'customers.data.0.password',
                    )
                    ->missing(
                        'customers.data.0.remember_token',
                    ),
            );
    }

    public function test_admin_can_view_customer_edit_page(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Editable Customer',
            'email' => 'editable@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.edit',
                    $customer,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Customers/Edit',
                    )
                    ->where(
                        'customer.id',
                        $customer->id,
                    )
                    ->where(
                        'customer.name',
                        'Editable Customer',
                    )
                    ->where(
                        'customer.email',
                        'editable@example.com',
                    ),
            );
    }

    public function test_admin_can_update_customer_profile(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Original Customer',
            'email' => 'original@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => '  Updated Customer  ',
                    'email' => '  UPDATED@EXAMPLE.COM  ',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.customers.show',
                    $customer,
                ),
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $customer->id,
                'name' => 'Updated Customer',
                'email' => 'updated@example.com',
            ],
        );
    }

    public function test_changing_customer_email_resets_verification(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => $customer->name,
                    'email' => 'new-email@example.com',
                ],
            )
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame(
            'new-email@example.com',
            $customer->email,
        );

        $this->assertNull(
            $customer->email_verified_at,
        );
    }

    public function test_unchanged_customer_email_preserves_verification(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $verifiedAt = now()
            ->startOfSecond();

        $customer = User::factory()->create([
            'email' => 'same@example.com',
            'email_verified_at' => $verifiedAt,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => 'Updated Name',
                    'email' => ' SAME@EXAMPLE.COM ',
                ],
            )
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertNotNull(
            $customer->email_verified_at,
        );

        $this->assertTrue(
            $customer
                ->email_verified_at
                ->equalTo(
                    $verifiedAt,
                ),
        );
    }

    public function test_customer_update_rejects_duplicate_normalized_email(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $customer = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.customers.edit',
                    $customer,
                ),
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => 'Customer',
                    'email' => ' EXISTING@EXAMPLE.COM ',
                ],
            )
            ->assertSessionHasErrors([
                'email',
            ]);

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $customer->id,
                'email' => 'customer@example.com',
            ],
        );
    }

    public function test_customer_update_cannot_modify_security_fields(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Secure Customer',
            'email' => 'secure@example.com',
            'email_verified_at' => now(),
        ]);

        $originalPassword =
            $customer->password;

        $originalStatus =
            $customer->status;

        $originalVerifiedAt =
            $customer->email_verified_at;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => 'Updated Secure Customer',
                    'email' => 'secure@example.com',

                    /*
                     * Deliberate malicious /
                     * unauthorized fields.
                     */
                    'password' => 'InjectedPassword1!',
                    'status' => 'suspended',
                    'email_verified_at' => null,
                    'remember_token' => 'injected-token',
                ],
            )
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame(
            'Updated Secure Customer',
            $customer->name,
        );

        $this->assertSame(
            $originalPassword,
            $customer->password,
        );

        $this->assertSame(
            $originalStatus,
            $customer->status,
        );

        $this->assertNotNull(
            $customer->email_verified_at,
        );

        $this->assertTrue(
            $customer
                ->email_verified_at
                ->equalTo(
                    $originalVerifiedAt,
                ),
        );
    }

    public function test_editor_cannot_edit_customer(): void
    {
        $editor = $this->createAdmin(
            AdminRole::Editor,
        );

        $customer = User::factory()->create();

        $this
            ->actingAs(
                $editor,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.edit',
                    $customer,
                ),
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $editor,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => 'Unauthorized',
                    'email' => 'unauthorized@example.com',
                ],
            )
            ->assertForbidden();
    }

    private function createAdmin(
        AdminRole $role,
    ): Admin {
        return Admin::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_customer_detail(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Detail Customer',
            'email' => 'detail@example.com',
            'email_verified_at' => now(),
        ]);

        Address::factory()
            ->for($customer)
            ->default()
            ->create([
                'recipient_name' => 'Detail Customer',
                'phone' => '+8801700000000',
                'address_line_1' => '123 Customer Road',
                'city' => 'Dhaka',
                'country_code' => 'BD',
            ]);

        Order::factory()
            ->count(2)
            ->create([
                'user_id' => $customer->id,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.show',
                    $customer,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Customers/Show',
                    )
                    ->where(
                        'customer.id',
                        $customer->id,
                    )
                    ->where(
                        'customer.name',
                        'Detail Customer',
                    )
                    ->where(
                        'customer.orders_count',
                        2,
                    )
                    ->where(
                        'customer.addresses_count',
                        1,
                    )
                    ->has(
                        'addresses',
                        1,
                    )
                    ->has(
                        'orders.data',
                        2,
                    ),
            );
    }

    public function test_customer_detail_only_contains_that_customers_addresses(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $first = User::factory()->create();

        $second = User::factory()->create();

        Address::factory()
            ->for($first)
            ->create([
                'recipient_name' => 'First Customer',
            ]);

        Address::factory()
            ->for($second)
            ->create([
                'recipient_name' => 'Second Customer',
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.show',
                    $first,
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'addresses',
                        1,
                    )
                    ->where(
                        'addresses.0.recipient_name',
                        'First Customer',
                    ),
            );
    }

    public function test_customer_detail_only_contains_that_customers_orders(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $first = User::factory()->create();

        $second = User::factory()->create();

        $firstOrder = Order::factory()->create([
            'user_id' => $first->id,
        ]);

        Order::factory()->create([
            'user_id' => $second->id,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.show',
                    $first,
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'orders.data',
                        1,
                    )
                    ->where(
                        'orders.data.0.id',
                        $firstOrder->id,
                    ),
            );
    }

    public function test_customer_order_history_is_paginated(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        Order::factory()
            ->count(15)
            ->create([
                'user_id' => $customer->id,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.show',
                    $customer,
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has(
                        'orders.data',
                        10,
                    )
                    ->where(
                        'orders.total',
                        15,
                    )
                    ->where(
                        'orders.current_page',
                        1,
                    ),
            );
    }

    public function test_editor_cannot_view_customer_detail(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Editor,
        );

        $customer = User::factory()->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.show',
                    $customer,
                ),
            )
            ->assertForbidden();
    }

    public function test_customer_detail_does_not_expose_authentication_secrets(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.customers.show',
                    $customer,
                ),
            )
            ->assertInertia(
                fn (Assert $page) => $page
                    ->missing(
                        'customer.password',
                    )
                    ->missing(
                        'customer.remember_token',
                    ),
            );
    }

    public function test_customer_without_transactional_or_audit_history_is_hard_deleted(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        Address::factory()
            ->for($customer)
            ->create();

        $customerId =
            $customer->id;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => 'Duplicate test account.',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.customers.index',
                ),
            );

        $this->assertDatabaseMissing(
            'users',
            [
                'id' => $customerId,
            ],
        );

        $this->assertDatabaseMissing(
            'addresses',
            [
                'user_id' => $customerId,
            ],
        );

        $this->assertDatabaseHas(
            'customer_account_audits',
            [
                'customer_id' => $customerId,
                'action' => CustomerAccountAudit::ACTION_DELETED,
            ],
        );
    }

    public function test_customer_with_orders_is_anonymized_not_deleted(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Historical Customer',
            'email' => 'historical@example.com',
            'email_verified_at' => now(),
            'status' => CustomerStatus::Active,
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => 'Customer removal request.',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.customers.show',
                    $customer,
                ),
            );

        $customer->refresh();

        $this->assertNotNull(
            $customer->anonymized_at,
        );

        $this->assertSame(
            'suspended',
            $customer->status->value,
        );

        $this->assertNotSame(
            'Historical Customer',
            $customer->name,
        );

        $this->assertNotSame(
            'historical@example.com',
            $customer->email,
        );

        $this->assertNull(
            $customer->email_verified_at,
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'user_id' => $customer->id,
            ],
        );

        $this->assertDatabaseHas(
            'customer_account_audits',
            [
                'customer_id' => $customer->id,
                'action' => CustomerAccountAudit::ACTION_ANONYMIZED,
            ],
        );
    }

    public function test_customer_with_account_audit_history_is_anonymized_even_without_orders(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'status' => CustomerStatus::Active,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->patch(
                route(
                    'admin.customers.block',
                    $customer,
                ),
                [
                    'reason' => 'Security review.',
                ],
            )
            ->assertSessionHasNoErrors();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => 'Remove account.',
                ],
            )
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertNotNull(
            $customer->anonymized_at,
        );

        $this->assertDatabaseHas(
            'customer_status_histories',
            [
                'user_id' => $customer->id,
            ],
        );
    }

    public function test_anonymization_redacts_saved_address_without_touching_order_snapshot(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        $address = Address::factory()
            ->for($customer)
            ->create([
                'recipient_name' => 'Private Person',
                'phone' => '+8801700000000',
                'address_line_1' => '123 Private Road',
                'city' => 'Dhaka',
                'country_code' => 'BD',
            ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,

            'delivery_address_id' => $address->id,

            'delivery_recipient_name' => 'Private Person',

            'delivery_phone' => '+8801700000000',

            'delivery_address_line_1' => '123 Private Road',

            'delivery_city' => 'Dhaka',

            'delivery_country_code' => 'BD',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => 'Privacy request.',
                ],
            )
            ->assertSessionHasNoErrors();

        $address->refresh();

        $order->refresh();

        $this->assertSame(
            'Anonymized Customer',
            $address->recipient_name,
        );

        $this->assertSame(
            'redacted',
            $address->phone,
        );

        $this->assertSame(
            'Redacted',
            $address->address_line_1,
        );

        /*
         * Immutable historical snapshot survives.
         */
        $this->assertSame(
            'Private Person',
            $order->delivery_recipient_name,
        );

        $this->assertSame(
            '+8801700000000',
            $order->delivery_phone,
        );

        $this->assertSame(
            '123 Private Road',
            $order->delivery_address_line_1,
        );
    }

    public function test_anonymized_customer_cannot_be_unblocked(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => 'Account removal.',
                ],
            )
            ->assertSessionHasNoErrors();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->patch(
                route(
                    'admin.customers.unblock',
                    $customer,
                ),
            )
            ->assertSessionHasErrors([
                'status',
            ]);

        $customer->refresh();

        $this->assertNotNull(
            $customer->anonymized_at,
        );

        $this->assertSame(
            'suspended',
            $customer->status->value,
        );
    }

    public function test_manager_cannot_remove_customer(): void
    {
        $manager = $this->createAdmin(
            AdminRole::Manager,
        );

        $customer = User::factory()->create();

        $this
            ->actingAs(
                $manager,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => 'Unauthorized removal.',
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $customer->id,
            ],
        );
    }

    public function test_customer_removal_requires_reason(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $customer,
                ),
                [
                    'reason' => '',
                ],
            )
            ->assertSessionHasErrors([
                'reason',
            ]);

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $customer->id,
            ],
        );
    }
}
