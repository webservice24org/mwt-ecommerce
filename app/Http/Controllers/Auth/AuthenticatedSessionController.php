<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Customer\Actions\LoginCustomerAction;
use App\Domain\Auth\Customer\Actions\LogoutCustomerAction;
use App\Domain\Auth\Customer\Data\LoginCustomerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
        ]);
    }

    public function store(
        LoginRequest $request,
        LoginCustomerAction $action,
    ): RedirectResponse {
        try {
            $action->execute(
                new LoginCustomerData(
                    email: $request
                        ->string('email')
                        ->toString(),

                    password: $request
                        ->string('password')
                        ->toString(),

                    remember: $request
                        ->boolean('remember'),
                ),
            );
        } catch (AuthenticationException) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            route('customer.dashboard'),
        );
    }

    public function destroy(
        Request $request,
        LogoutCustomerAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->session(),
        );

        return redirect()->route('home');
    }
}
