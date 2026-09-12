<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Actions;

use App\Domain\Auth\Admin\Data\AdminLoginData;
use App\Models\Admin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final readonly class LoginAdminAction
{
    /**
     * @throws AuthenticationException
     */
    public function execute(
        AdminLoginData $data,
    ): Admin {
        $authenticated = Auth::guard('admin')->attempt(
            [
                'email' => $data->email,
                'password' => $data->password,
                'is_active' => true,
            ],
            $data->remember,
        );

        if (! $authenticated) {
            throw new AuthenticationException(
                'Invalid credentials.',
            );
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof Admin) {
            Auth::guard('admin')->logout();

            throw new AuthenticationException(
                'Unable to authenticate admin.',
            );
        }

        return $admin;
    }
}
