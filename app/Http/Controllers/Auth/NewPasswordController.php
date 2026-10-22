<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetCustomerPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class NewPasswordController extends Controller
{
    public function create(
        Request $request,
    ): Response {
        return Inertia::render(
            'Auth/ResetPassword',
            [
                'email' => $request->string(
                    'email',
                )->toString(),
                'token' => (string) $request->route(
                    'token',
                ),
            ],
        );
    }

    /**
     * @throws ValidationException
     */
    public function store(
        ResetCustomerPasswordRequest $request,
    ): RedirectResponse {
        $status = Password::reset(
            $request->resetCredentials(),
            static function (
                mixed $resetUser,
                string $password,
            ): void {
                if (
                    ! $resetUser instanceof User
                    || ! $resetUser->isActive()
                ) {
                    throw ValidationException::withMessages([
                        'email' => trans(
                            'passwords.user',
                        ),
                    ]);
                }

                /*
                 * User has a hashed password cast, so assign
                 * the plaintext validated password exactly once.
                 * Rotate remember_token to invalidate persistent
                 * remember-me authentication after a reset.
                 */
                $resetUser->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(
                        60,
                    ),
                ])->save();

                event(
                    new PasswordReset(
                        $resetUser,
                    ),
                );
            },
        );

        if (
            $status === Password::PASSWORD_RESET
        ) {
            return redirect()
                ->route(
                    'login',
                )
                ->with(
                    'status',
                    trans(
                        $status,
                    ),
                );
        }

        throw ValidationException::withMessages([
            'email' => [
                trans(
                    $status,
                ),
            ],
        ]);
    }
}
