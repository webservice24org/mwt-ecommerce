<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteCustomerAddressAction
{
    public function execute(
        User $user,
        Address $address,
    ): void {
        DB::transaction(
            function () use ($user, $address): void {
                $wasDefault =
                    $address->is_default;

                $address->delete();

                if (! $wasDefault) {
                    return;
                }

                $replacement = $user
                    ->addresses()
                    ->lockForUpdate()
                    ->orderBy('id')
                    ->first();

                if ($replacement === null) {
                    return;
                }

                $replacement->update([
                    'is_default' => true,
                ]);
            },
        );
    }
}
