<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;

final class RegisterCustomerAction
{
    public function execute(
        string $name,
        string $email,
        string $password,
    ): User {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'status' => CustomerStatus::Active,
        ]);
    }
}
