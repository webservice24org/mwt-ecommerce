<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SetDefaultCustomerAddressAction
{
    public function execute(
        User $user,
        Address $address,
    ): Address {
        return DB::transaction(
            function () use ($user, $address): Address {
                /*
                 * Lock this customer's address rows while the
                 * default-address invariant is being changed.
                 */
                $user
                    ->addresses()
                    ->lockForUpdate()
                    ->get([
                        'id',
                        'is_default',
                    ]);

                $user
                    ->addresses()
                    ->where(
                        'is_default',
                        true,
                    )
                    ->whereKeyNot(
                        $address->id,
                    )
                    ->update([
                        'is_default' => false,
                    ]);

                if (! $address->is_default) {
                    $address->update([
                        'is_default' => true,
                    ]);
                }

                return $address->refresh();
            },
        );
    }
}
