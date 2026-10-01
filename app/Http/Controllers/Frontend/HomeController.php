<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\Catalog\Queries\Storefront\StorefrontHomeQuery;
use App\Domain\PageBuilder\Queries\GetStorefrontHomepageQuery;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function index(
        StorefrontHomeQuery $homeQuery,
        GetStorefrontHomepageQuery $homepageQuery,
    ): Response {
        return Inertia::render(
            'Frontend/Home',
            [
                /*
                 * Keep the existing storefront
                 * payload during migration.
                 *
                 * E.8.2 will use this as the
                 * fallback when no builder
                 * homepage exists.
                 */
                'home' => $homeQuery
                    ->get()
                    ->toArray(),

                /*
                 * Builder-driven homepage.
                 *
                 * null means the storefront
                 * should continue rendering
                 * the existing manual homepage.
                 */
                'builderPage' => $homepageQuery->get(),
            ],
        );
    }
}
