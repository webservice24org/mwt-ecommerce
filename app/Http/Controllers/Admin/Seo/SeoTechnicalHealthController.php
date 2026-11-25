<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\Ai\Workspace\Queries\GetSeoTechnicalHealthQuery;
use Inertia\Inertia;
use Inertia\Response;

final class SeoTechnicalHealthController
{
    public function __invoke(
        GetSeoTechnicalHealthQuery $health,
    ): Response {
        return Inertia::render(
            'Admin/Seo/TechnicalHealth',
            [
                'health' => $health->handle(),
            ],
        );
    }
}
