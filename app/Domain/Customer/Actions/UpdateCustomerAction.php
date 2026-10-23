<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Data\UpdateCustomerData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateCustomerAction
{
    /**
     * @throws ValidationException
     */
    public function execute(
        User $customer,
        UpdateCustomerData $data,
    ): User {
        return DB::transaction(
            function () use ($customer, $data): User {
                $lockedCustomer = User::query()
                    ->whereKey(
                        $customer->getKey(),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedCustomer->isAnonymized()) {
                    throw ValidationException::withMessages([
                        'customer' => 'An anonymized customer account cannot be edited.',
                    ]);
                }

                $emailChanged =
                    mb_strtolower(
                        (string) $lockedCustomer->email,
                    )
                    !== $data->email;

                /**
                 * Explicit field whitelist.
                 *
                 * Never replace this with request mass assignment.
                 *
                 * @var array<string, mixed> $attributes
                 */
                $attributes = [
                    'name' => $data->name,
                    'email' => $data->email,
                ];

                if ($emailChanged) {
                    $attributes[
                        'email_verified_at'
                    ] = null;
                }

                $lockedCustomer
                    ->forceFill(
                        $attributes,
                    )
                    ->save();

                return $lockedCustomer
                    ->refresh();
            },
        );
    }
}
