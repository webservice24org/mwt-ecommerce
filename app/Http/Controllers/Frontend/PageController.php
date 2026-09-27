<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Domain\PageBuilder\Queries\StorefrontPageQuery;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class PageController extends Controller
{
    public function __invoke(
        string $slug,
        StorefrontPageQuery $query,
    ): Response {
        $page = $query->findBySlugOrFail(
            $slug,
        );

        return Inertia::render(
            'Frontend/Pages/Show',
            [
                'page' => $page->toArray(),
            ],
        );
    }
}
