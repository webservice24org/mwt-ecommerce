<?php

declare(strict_types=1);

namespace App\Domain\Auth\Customer\Actions;

use App\Domain\Auth\Customer\Data\RegisterCustomerData;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

final readonly class RegisterCustomerAction
{
    public function execute(
        RegisterCustomerData $data,
    ): User {
        $user = DB::transaction(
            function () use ($data): User {
                return User::query()->create([
                    'name' => $data->name,
                    'email' => $data->email,
                    'password' => $data->password,
                    'status' => CustomerStatus::Active,
                ]);
            },
        );

        event(new Registered($user));

        return $user;
    }
}
