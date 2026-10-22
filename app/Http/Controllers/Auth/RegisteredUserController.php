<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Customer\Actions\RegisterCustomerAction;
use App\Domain\Mail\Actions\ConfigureRuntimeMailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use Illuminate\Auth\Events\Registered;
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
        RegisterCustomerAction $registerCustomer,
        ConfigureRuntimeMailAction $configureRuntimeMail,
    ): RedirectResponse {
        $user = $registerCustomer->execute(
            name: $request->customerName(),
            email: $request->customerEmail(),
            password: $request->customerPassword(),
        );

        /*
         * Laravel's Registered event sends the built-in
         * verification notification synchronously.
         *
         * Configure the database-backed mail transport in
         * this HTTP process before dispatching that event.
         */
        $configureRuntimeMail->execute();

        event(
            new Registered(
                $user,
            ),
        );

        Auth::guard(
            'web',
        )->login(
            $user,
        );

        $request
            ->session()
            ->regenerate();

        return redirect()->route(
            'verification.notice',
        );
    }
}
