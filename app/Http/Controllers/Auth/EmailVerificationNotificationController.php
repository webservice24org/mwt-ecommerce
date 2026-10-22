<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Mail\Actions\ConfigureRuntimeMailAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmailVerificationNotificationController extends Controller
{
    public function store(
        Request $request,
        ConfigureRuntimeMailAction $configureRuntimeMail,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        if ($user->hasVerifiedEmail()) {
            return redirect()->route(
                'customer.dashboard',
            );
        }

        /*
         * Resends happen in a new HTTP request, so runtime
         * mail configuration from registration cannot be
         * assumed to still exist in this PHP process.
         */
        $configureRuntimeMail->execute();

        $user->sendEmailVerificationNotification();

        return back()->with(
            'status',
            'verification-link-sent',
        );
    }
}
