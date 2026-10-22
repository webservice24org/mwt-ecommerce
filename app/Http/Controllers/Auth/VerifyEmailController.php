<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final class VerifyEmailController extends Controller
{
    public function __invoke(
        EmailVerificationRequest $request,
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(
                    new Verified(
                        $user,
                    ),
                );
            }
        }

        return redirect()->route(
            'customer.dashboard',
            [
                'verified' => 1,
            ],
        );
    }
}
