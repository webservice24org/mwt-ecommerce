<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Domain\Seo\GenerativeSearch\Queries\GetGenerativeSearchReadinessWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GenerativeSearchReadinessWorkspaceController
{
    public function __invoke(
        Request $request,
        GetGenerativeSearchReadinessWorkspaceQuery $query,
    ): Response {
        $payload = $query->handle(
            entityType: $this->stringQuery(
                request: $request,
                key: 'entity_type',
                default: 'product',
            ),
            status: $this->nullableStringQuery(
                request: $request,
                key: 'status',
            ),
            search: $this->nullableStringQuery(
                request: $request,
                key: 'search',
            ),
            page: max(
                1,
                $request->integer(
                    'page',
                    1,
                ),
            ),
            perPage: $request->integer(
                'per_page',
                25,
            ),
        );

        return Inertia::render(
            'Admin/Seo/GenerativeSearch/Readiness',
            $payload,
        );
    }

    private function stringQuery(
        Request $request,
        string $key,
        string $default,
    ): string {
        $value = $request->query(
            $key,
            $default,
        );

        return is_string($value)
            ? $value
            : $default;
    }

    private function nullableStringQuery(
        Request $request,
        string $key,
    ): ?string {
        $value = $request->query($key);

        return is_string($value)
            ? $value
            : null;
    }
}
