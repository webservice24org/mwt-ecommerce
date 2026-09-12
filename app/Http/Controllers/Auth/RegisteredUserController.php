<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Customer\Actions\RegisterCustomerAction;
use App\Domain\Auth\Customer\Data\RegisterCustomerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render(
            'Auth/Register',
        );
    }

    public function store(
        RegisterCustomerRequest $request,
        RegisterCustomerAction $action,
    ): RedirectResponse {
        $user = $action->execute(
            new RegisterCustomerData(
                name: $request
                    ->string('name')
                    ->toString(),

                email: $request
                    ->string('email')
                    ->toString(),

                password: $request
                    ->string('password')
                    ->toString(),
            ),
        );

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->route(
            'customer.dashboard',
        );
    }
}
