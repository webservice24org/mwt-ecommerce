<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

final class ResetPasswordController extends Controller
{
    public function create(
        Request $request,
        string $token,
    ): Response {
        return Inertia::render(
            'Admin/Auth/ResetPassword',
            [
                'token' => $token,
                'email' => $request->string('email')->toString(),
            ],
        );
    }

    public function store(
        Request $request,
    ): RedirectResponse {
        $request->validate([
            'token' => [
                'required',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'confirmed',

                PasswordRule::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $status = Password::broker('admins')->reset(
            [
                'email' => strtolower(
                    trim(
                        (string) $request->input('email'),
                    ),
                ),

                'password' => (string) $request->input('password'),

                'password_confirmation' => (string) $request->input(
                    'password_confirmation',
                ),

                'token' => (string) $request->input('token'),
            ],

            function (
                Admin $admin,
                string $password,
            ): void {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(
                    new PasswordReset($admin),
                );
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withErrors([
                'email' => __($status),
            ]);
        }

        return redirect()
            ->route('admin.login')
            ->with(
                'status',
                'Your password has been reset.',
            );
    }
}
