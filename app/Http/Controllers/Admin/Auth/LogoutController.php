<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Domain\Auth\Admin\Actions\LogoutAdminAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LogoutController extends Controller
{
    public function __invoke(
        Request $request,
        LogoutAdminAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->session(),
        );

        return redirect()->route(
            'admin.login',
        );
    }
}
