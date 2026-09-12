<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAdminHasRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles,
    ): Response {
        $admin = $request->user('admin');

        if (! $admin instanceof Admin) {
            abort(403);
        }

        $allowedRoles = array_map(
            static fn (string $role): AdminRole => AdminRole::from($role),
            $roles,
        );

        if (! in_array($admin->role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
