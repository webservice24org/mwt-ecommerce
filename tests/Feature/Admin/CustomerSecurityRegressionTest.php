<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\Admin;
use App\Models\CustomerAccountAudit;
use App\Models\CustomerStatusHistory;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_maintain_customer_but_cannot_remove_customer(): void
    {
        $manager = $this->createAdmin(
            AdminRole::Manager,
        );

        $customer = User::factory()->create([
            'name' => 'Manager Customer',
            'email' => 'manager-customer@example.com',
            'status' => CustomerStatus::Active,
        ]);

        /*
         * Manager may update ordinary profile data.
         */
        $this
            ->actingAs(
                $manager,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $customer,
                ),
                [
                    'name' => 'Updated Customer',
                    'email' => 'updated-customer@example.com',
                ],
            )
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame(
            'Updated Customer',
            $customer->name,
        );

        /*
         * Manager may block.
         */
        $this
            ->actingAs(
                $manager,
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

        $customer->refresh();

        $this->assertSame(
            'suspended',
            $customer->status->value,
        );

        /*
         * Manager may unblock.
         */
        $this
            ->actingAs(
                $manager,
                'admin',
            )
            ->patch(
                route(
                    'admin.customers.unblock',
                    $customer,
                ),
                [
                    'reason' => 'Review completed.',
                ],
            )
            ->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame(
            CustomerStatus::Active,
            $customer->status,
        );

        /*
         * Destructive removal remains Admin /
         * Super Admin only.
         */
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

    public function test_editor_cannot_invoke_any_customer_mutation_endpoint(): void
    {
        $editor = $this->createAdmin(
            AdminRole::Editor,
        );

        $customer = User::factory()->create([
            'name' => 'Protected Customer',
            'email' => 'protected@example.com',
            'status' => CustomerStatus::Active,
        ]);

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
                    'name' => 'Unauthorized Update',
                    'email' => 'unauthorized@example.com',
                ],
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $editor,
                'admin',
            )
            ->patch(
                route(
                    'admin.customers.block',
                    $customer,
                ),
                [
                    'reason' => 'Unauthorized block.',
                ],
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $editor,
                'admin',
            )
            ->patch(
                route(
                    'admin.customers.unblock',
                    $customer,
                ),
                [
                    'reason' => 'Unauthorized unblock.',
                ],
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $editor,
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

        $customer->refresh();

        $this->assertSame(
            'Protected Customer',
            $customer->name,
        );

        $this->assertSame(
            'protected@example.com',
            $customer->email,
        );

        $this->assertSame(
            CustomerStatus::Active,
            $customer->status,
        );
    }

    public function test_route_bound_customer_is_authoritative_for_update(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $first = User::factory()->create([
            'name' => 'First Customer',
            'email' => 'first-route@example.com',
        ]);

        $second = User::factory()->create([
            'name' => 'Second Customer',
            'email' => 'second-route@example.com',
        ]);

        /*
         * Attempt to smuggle another customer ID through
         * the request body.
         *
         * The route-bound customer must remain authoritative.
         */
        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.customers.update',
                    $first,
                ),
                [
                    'id' => $second->id,

                    'customer_id' => $second->id,

                    'name' => 'First Customer Updated',

                    'email' => 'first-updated@example.com',
                ],
            )
            ->assertSessionHasNoErrors();

        $first->refresh();
        $second->refresh();

        $this->assertSame(
            'First Customer Updated',
            $first->name,
        );

        $this->assertSame(
            'first-updated@example.com',
            $first->email,
        );

        $this->assertSame(
            'Second Customer',
            $second->name,
        );

        $this->assertSame(
            'second-route@example.com',
            $second->email,
        );
    }

    public function test_client_cannot_choose_hard_delete_or_anonymization_mode(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        /*
         * Transactional history means anonymization,
         * even when the browser requests hard deletion.
         */
        $historicalCustomer = User::factory()->create([
            'email' => 'history@example.com',
        ]);

        Order::factory()->create([
            'user_id' => $historicalCustomer->id,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $historicalCustomer,
                ),
                [
                    'reason' => 'Remove historical account.',

                    /*
                     * Deliberately malicious / unsupported.
                     */
                    'mode' => 'hard_delete',
                ],
            )
            ->assertSessionHasNoErrors();

        $historicalCustomer->refresh();

        $this->assertNotNull(
            $historicalCustomer->anonymized_at,
        );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $historicalCustomer->id,
            ],
        );

        /*
         * No transactional/audit history means hard delete,
         * even when the browser tries to force anonymization.
         */
        $cleanCustomer = User::factory()->create();

        $cleanCustomerId =
            $cleanCustomer->id;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.customers.destroy',
                    $cleanCustomer,
                ),
                [
                    'reason' => 'Remove empty account.',

                    /*
                     * Deliberately malicious / unsupported.
                     */
                    'mode' => 'anonymize',
                ],
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'users',
            [
                'id' => $cleanCustomerId,
            ],
        );
    }

    public function test_anonymized_customer_is_a_terminal_account_state(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Terminal Customer',
            'email' => 'terminal@example.com',
            'status' => CustomerStatus::Active,
        ]);

        Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        /*
         * Existing order forces anonymization.
         */
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

        $customer->refresh();

        $this->assertNotNull(
            $customer->anonymized_at,
        );

        /*
         * Editing is forbidden by UpdateCustomerAction.
         */
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
                    'name' => 'Restored Customer',
                    'email' => 'restored@example.com',
                ],
            )
            ->assertSessionHasErrors([
                'customer',
            ]);

        /*
         * Blocking again is forbidden.
         */
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
                    'reason' => 'Invalid transition.',
                ],
            )
            ->assertSessionHasErrors([
                'status',
            ]);

        /*
         * Most importantly, reactivation is forbidden.
         */
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
                [
                    'reason' => 'Attempted restoration.',
                ],
            )
            ->assertSessionHasErrors([
                'status',
            ]);

        /*
         * Repeat removal/anonymization is forbidden too.
         */
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
                    'reason' => 'Second removal attempt.',
                ],
            )
            ->assertSessionHasErrors([
                'customer',
            ]);

        $customer->refresh();

        $this->assertNotNull(
            $customer->anonymized_at,
        );

        $this->assertSame(
            'suspended',
            $customer->status->value,
        );

        $this->assertNotSame(
            'restored@example.com',
            $customer->email,
        );
    }

    public function test_block_and_unblock_history_records_actor_and_reason(): void
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
                    'reason' => 'Manual fraud review.',
                ],
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'customer_status_histories',
            [
                'user_id' => $customer->id,
                'from_status' => 'active',
                'to_status' => 'suspended',
                'reason' => 'Manual fraud review.',
                'actor_id' => $admin->id,
                'actor_name' => $admin->name,
            ],
        );

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
                [
                    'reason' => 'Fraud review cleared.',
                ],
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'customer_status_histories',
            [
                'user_id' => $customer->id,
                'from_status' => 'suspended',
                'to_status' => 'active',
                'reason' => 'Fraud review cleared.',
                'actor_id' => $admin->id,
                'actor_name' => $admin->name,
            ],
        );

        $this->assertSame(
            2,
            CustomerStatusHistory::query()
                ->where(
                    'user_id',
                    $customer->id,
                )
                ->count(),
        );
    }

    public function test_hard_delete_audit_does_not_copy_customer_identity(): void
    {
        $admin = $this->createAdmin(
            AdminRole::Admin,
        );

        $customer = User::factory()->create([
            'name' => 'Sensitive Customer',
            'email' => 'sensitive@example.com',
        ]);

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
                    'reason' => 'Duplicate account.',
                ],
            )
            ->assertSessionHasNoErrors();

        $audit = CustomerAccountAudit::query()
            ->where(
                'customer_id',
                $customerId,
            )
            ->firstOrFail();

        $serialized = json_encode(
            $audit->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'Sensitive Customer',
            $serialized,
        );

        $this->assertStringNotContainsString(
            'sensitive@example.com',
            $serialized,
        );

        $this->assertSame(
            CustomerAccountAudit::ACTION_DELETED,
            $audit->action,
        );
    }

    private function createAdmin(
        AdminRole $role,
    ): Admin {
        return Admin::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
