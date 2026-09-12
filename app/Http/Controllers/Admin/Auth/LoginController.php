<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Domain\Auth\Admin\Actions\LoginAdminAction;
use App\Domain\Auth\Admin\Data\AdminLoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    public function store(
        LoginRequest $request,
        LoginAdminAction $action,
    ): RedirectResponse {
        try {
            $action->execute(
                new AdminLoginData(
                    email: $request->string('email')->toString(),
                    password: $request->string('password')->toString(),
                    remember: $request->boolean('remember'),
                ),
            );
        } catch (AuthenticationException) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            route('admin.dashboard'),
        );
    }
}
