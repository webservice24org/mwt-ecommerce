<?php

declare(strict_types=1);

namespace App\Domain\Auth\Customer\Actions;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

final readonly class LogoutCustomerAction
{
    public function execute(
        Session $session,
    ): void {
        Auth::guard('web')->logout();

        $session->invalidate();

        $session->regenerateToken();
    }
}
