<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Actions;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

final readonly class LogoutAdminAction
{
    public function execute(Session $session): void
    {
        Auth::guard('admin')->logout();

        $session->invalidate();
        $session->regenerateToken();
    }
}
