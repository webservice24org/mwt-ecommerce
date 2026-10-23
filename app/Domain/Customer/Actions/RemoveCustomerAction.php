<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Enums\CustomerRemovalOutcome;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\Admin;
use App\Models\CustomerAccountAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RemoveCustomerAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        User $customer,
        Admin $actor,
        string $reason,
    ): CustomerRemovalOutcome {
        return DB::transaction(
            function () use ($customer, $actor, $reason): CustomerRemovalOutcome {
                $lockedCustomer = User::query()
                    ->whereKey(
                        $customer->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedCustomer->isAnonymized()
                ) {
                    throw ValidationException::withMessages([
                        'customer' => 'This customer account has already been anonymized.',
                    ]);
                }

                $hasOrders = $lockedCustomer
                    ->orders()
                    ->exists();

                $hasStatusHistory = $lockedCustomer
                    ->statusHistories()
                    ->exists();

                /*
                 * Orders and administrative status history
                 * are historical records that must survive.
                 */
                $mustPreserve =
                    $hasOrders
                    || $hasStatusHistory;

                if (! $mustPreserve) {
                    return $this->hardDelete(
                        customer: $lockedCustomer,
                        actor: $actor,
                        reason: $reason,
                    );
                }

                return $this->anonymize(
                    customer: $lockedCustomer,
                    actor: $actor,
                    reason: $reason,
                    hasOrders: $hasOrders,
                    hasStatusHistory: $hasStatusHistory,
                );
            },
        );
    }

    private function hardDelete(
        User $customer,
        Admin $actor,
        string $reason,
    ): CustomerRemovalOutcome {
        $customerId = $customer->id;

        /*
         * Saved addresses are live account data,
         * not historical order snapshots.
         *
         * No orders exist in this branch.
         */
        $customer
            ->addresses()
            ->delete();

        CustomerAccountAudit::query()
            ->create([
                'customer_id' => $customerId,

                'action' => CustomerAccountAudit::ACTION_DELETED,

                'actor_type' => $actor
                    ->getMorphClass(),

                'actor_id' => $actor
                    ->getKey(),

                'actor_name' => $actor
                    ->name,

                'reason' => $reason,

                'metadata' => [
                    'orders_present' => false,
                    'status_history_present' => false,
                ],

                'occurred_at' => now(),
            ]);

        $customer->delete();

        return CustomerRemovalOutcome::Deleted;
    }

    private function anonymize(
        User $customer,
        Admin $actor,
        string $reason,
        bool $hasOrders,
        bool $hasStatusHistory,
    ): CustomerRemovalOutcome {
        $blockedStatus = CustomerStatus::from(
            'suspended',
        );

        /*
         * Keep address rows if orders may still reference
         * delivery_address_id, but remove live PII.
         *
         * Historical order delivery snapshots remain untouched.
         */
        $customer
            ->addresses()
            ->update([
                'label' => 'Anonymized',

                'recipient_name' => 'Anonymized Customer',

                'phone' => 'redacted',

                'address_line_1' => 'Redacted',

                'address_line_2' => null,

                'city' => 'Redacted',

                'state_or_region' => null,

                'postal_code' => null,

                'country_code' => 'XX',

                'is_default' => false,
            ]);

        /*
         * Generate an address that can never collide with
         * a real customer address and cannot receive mail.
         */
        $anonymousEmail = sprintf(
            'deleted-customer-%d-%s@example.invalid',
            $customer->id,
            Str::lower(
                (string) Str::ulid(),
            ),
        );

        $customer->forceFill([
            'name' => sprintf(
                'Deleted Customer #%d',
                $customer->id,
            ),

            'email' => $anonymousEmail,

            /*
             * A random password invalidates the old secret.
             * The User password cast hashes it automatically.
             */
            'password' => Str::random(
                64,
            ),

            'remember_token' => null,

            'email_verified_at' => null,

            'status' => $blockedStatus,

            'anonymized_at' => now(),
        ])->save();

        CustomerAccountAudit::query()
            ->create([
                'customer_id' => $customer->id,

                'action' => CustomerAccountAudit::ACTION_ANONYMIZED,

                'actor_type' => $actor
                    ->getMorphClass(),

                'actor_id' => $actor
                    ->getKey(),

                'actor_name' => $actor
                    ->name,

                'reason' => $reason,

                'metadata' => [
                    'orders_present' => $hasOrders,
                    'status_history_present' => $hasStatusHistory,
                ],

                'occurred_at' => now(),
            ]);

        return CustomerRemovalOutcome::Anonymized;
    }
}
