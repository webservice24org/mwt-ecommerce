<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerDashboardController extends Controller
{
    public function __invoke(
        Request $request,
    ): Response {
        $user = $request->user();

        abort_unless(
            $user instanceof User,
            401,
        );

        /*
         * Query exclusively through the authenticated
         * customer's relationships.
         *
         * Never count global Order or Address records
         * and filter them in PHP.
         */
        $orderCount = $user
            ->orders()
            ->count();

        $addressCount = $user
            ->addresses()
            ->count();

        return Inertia::render(
            'Customer/Dashboard',
            [
                'customer' => [
                    'id' => $user->id,

                    'name' => $user->name,

                    'email' => $user->email,

                    'email_verified_at' => $user->email_verified_at,
                ],

                'summary' => [
                    'orders' => $orderCount,

                    'addresses' => $addressCount,
                ],
            ],
        );
    }
}
