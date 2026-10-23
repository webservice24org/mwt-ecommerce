<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BlockCustomerAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        User $customer,
        Admin $actor,
        string $reason,
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
                        'status' => 'This customer account has already been anonymized.',
                    ]);
                }

                $blockedStatus = CustomerStatus::from(
                    'suspended',
                );

                if (
                    $lockedCustomer->status
                    !== CustomerStatus::Active
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Only an active customer can be blocked.',
                    ]);
                }

                $fromStatus =
                    $lockedCustomer->status;

                $lockedCustomer->status =
                    $blockedStatus;

                $lockedCustomer->save();

                $lockedCustomer
                    ->statusHistories()
                    ->create([
                        'from_status' => $fromStatus
                            ->value,
                        'to_status' => $blockedStatus
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
