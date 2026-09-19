<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function index(
        StorefrontHomeQuery $homeQuery,
    ): Response {
        return Inertia::render(
            'Frontend/Home',
            [
                'home' => $homeQuery
                    ->get()
                    ->toArray(),
            ],
        );
    }
}
