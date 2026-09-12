<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdminIsActive
{
    public function handle(
        Request $request,
        Closure $next,
    ): Response|RedirectResponse {
        $admin = $request->user('admin');

        if (! $admin instanceof Admin) {
            return $next($request);
        }

        if (! $admin->is_active) {
            Auth::guard('admin')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'email' => 'Your administrator account is currently unavailable.',
                ]);
        }

        return $next($request);
    }
}
