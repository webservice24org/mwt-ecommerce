<?php

declare(strict_types=1);

namespace App\Domain\Auth\Customer\Actions;

use App\Domain\Auth\Customer\Data\LoginCustomerData;
use App\Domain\Customer\Enums\CustomerStatus;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final readonly class LoginCustomerAction
{
    /**
     * @throws AuthenticationException
     */
    public function execute(
        LoginCustomerData $data,
    ): User {
        $authenticated = Auth::guard('web')->attempt(
            [
                'email' => $data->email,
                'password' => $data->password,
                'status' => CustomerStatus::Active->value,
            ],
            $data->remember,
        );

        if (! $authenticated) {
            throw new AuthenticationException(
                'Invalid credentials.',
            );
        }

        $user = Auth::guard('web')->user();

        if (! $user instanceof User) {
            Auth::guard('web')->logout();

            throw new AuthenticationException(
                'Unable to authenticate customer.',
            );
        }

        return $user;
    }
}
