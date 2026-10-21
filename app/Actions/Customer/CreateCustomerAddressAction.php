<?php

declare(strict_types=1);

namespace App\Actions\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateCustomerAddressAction
{
    /**
     * @param  array{
     *     label: string|null,
     *     recipient_name: string,
     *     phone: string,
     *     address_line_1: string,
     *     address_line_2: string|null,
     *     city: string,
     *     state_or_region: string|null,
     *     postal_code: string|null,
     *     country_code: string,
     *     is_default: bool
     * }  $data
     */
    public function execute(
        User $user,
        array $data,
    ): Address {
        return DB::transaction(
            function () use ($user, $data): Address {
                $hasAddresses = $user
                    ->addresses()
                    ->exists();

                $shouldBeDefault =
                    ! $hasAddresses ||
                    $data['is_default'];

                if ($shouldBeDefault) {
                    $user
                        ->addresses()
                        ->where(
                            'is_default',
                            true,
                        )
                        ->update([
                            'is_default' => false,
                        ]);
                }

                /** @var Address $address */
                $address = $user
                    ->addresses()
                    ->create([
                        'label' => $data['label'],
                        'recipient_name' => $data['recipient_name'],
                        'phone' => $data['phone'],
                        'address_line_1' => $data['address_line_1'],
                        'address_line_2' => $data['address_line_2'],
                        'city' => $data['city'],
                        'state_or_region' => $data['state_or_region'],
                        'postal_code' => $data['postal_code'],
                        'country_code' => strtoupper(
                            $data['country_code'],
                        ),
                        'is_default' => $shouldBeDefault,
                    ]);

                return $address;
            },
        );
    }
}
