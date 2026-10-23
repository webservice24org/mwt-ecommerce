<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UnblockCustomerAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        User $customer,
        Admin $actor,
        ?string $reason = null,
    ): User {
        return DB::transaction(
            function () use ($customer, $actor, $reason): User {
                $lockedCustomer = User::query()
                    ->whereKey(
                        $customer->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedCustomer->isAnonymized()) {
                    throw ValidationException::withMessages([
                        'status' => 'An anonymized customer account cannot be reactivated.',
                    ]);
                }

                $blockedStatus = CustomerStatus::from(
                    'suspended',
                );

                if (
                    $lockedCustomer->status
                    !== $blockedStatus
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only a blocked customer can be unblocked.',
                    ]);
                }

                $fromStatus =
                    $lockedCustomer->status;

                $lockedCustomer->status =
                    CustomerStatus::Active;

                $lockedCustomer->save();

                $lockedCustomer
                    ->statusHistories()
                    ->create([
                        'from_status' => $fromStatus
                            ->value,
                        'to_status' => CustomerStatus::Active
                            ->value,
                        'reason' => $reason,
                        'actor_type' => $actor
                            ->getMorphClass(),
                        'actor_id' => $actor
                            ->getKey(),
                        'actor_name' => $actor
                            ->name,
                        'changed_at' => now(),
                    ]);

                return $lockedCustomer
                    ->refresh();
            },
        );
    }
}
