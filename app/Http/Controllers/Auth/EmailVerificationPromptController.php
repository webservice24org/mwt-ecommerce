<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EmailVerificationPromptController extends Controller
{
    public function __invoke(
        Request $request,
    ): Response|RedirectResponse {
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

        return Inertia::render(
            'Auth/VerifyEmail',
            [
                'email' => $user->email,
                'status' => $request
                    ->session()
                    ->get(
                        'status',
                    ),
            ],
        );
    }
}
