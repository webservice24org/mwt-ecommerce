<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Mail\Actions\ConfigureRuntimeMailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetLinkRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render(
            'Auth/ForgotPassword',
            [
                'status' => session(
                    'status',
                ),
            ],
        );
    }

    public function store(
        SendPasswordResetLinkRequest $request,
        ConfigureRuntimeMailAction $configureRuntimeMail,
    ): RedirectResponse {
        /*
         * Password reset is another transactional email path.
         * Configure the same database-backed SMTP transport
         * used by order and verification emails.
         */
        $configureRuntimeMail->execute();

        Password::sendResetLink(
            [
                'email' => $request->customerEmail(),
            ],
            static function (
                mixed $notifiable,
                string $token,
            ): void {
                /*
                 * Do not issue reset mail for an inactive
                 * customer. The HTTP response remains generic
                 * so account existence/status is not exposed.
                 */
                if (
                    ! $notifiable instanceof User
                    || ! $notifiable->isActive()
                ) {
                    return;
                }

                $notifiable
                    ->sendPasswordResetNotification(
                        $token,
                    );
            },
        );

        return back()->with(
            'status',
            'If an eligible account exists for that email address, a password reset link has been sent.',
        );
    }
}
