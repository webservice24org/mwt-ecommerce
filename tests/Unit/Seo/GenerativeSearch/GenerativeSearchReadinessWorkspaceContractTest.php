<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Queries\GetGenerativeSearchReadinessWorkspaceQuery;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class GenerativeSearchReadinessWorkspaceContractTest extends TestCase
{
    public function test_workspace_route_is_read_only_get_surface(): void
    {
        self::assertTrue(
            Route::has(
                'admin.seo.generative-search.readiness',
            ),
        );

        $route =
            Route::getRoutes()
                ->getByName(
                    'admin.seo.generative-search.readiness',
                );

        self::assertNotNull(
            $route,
        );

        self::assertSame(
            [
                'GET',
                'HEAD',
            ],
            $route
                ->methods(),
        );
    }

    public function test_workspace_is_bounded_and_has_no_numeric_ai_score_contract(): void
    {
        self::assertSame(
            250,
            GetGenerativeSearchReadinessWorkspaceQuery::SCAN_LIMIT,
        );

        $query =
            $this->source(
                'app/Domain/Seo/GenerativeSearch/Queries/GetGenerativeSearchReadinessWorkspaceQuery.php',
            );

        $page =
            $this->source(
                'resources/js/Pages/Admin/Seo/GenerativeSearch/Readiness.tsx',
            );

        self::assertStringNotContainsString(
            "'score' =>",
            $query,
        );

        self::assertStringNotContainsString(
            'score:',
            $page,
        );

        self::assertStringContainsString(
            'does not estimate AI ranking or citation probability',
            $page,
        );
    }

    public function test_workspace_has_no_mutation_provider_or_automatic_optimization_path(): void
    {
        $query =
            $this->source(
                'app/Domain/Seo/GenerativeSearch/Queries/GetGenerativeSearchReadinessWorkspaceQuery.php',
            );

        $controller =
            $this->source(
                'app/Http/Controllers/Admin/Seo/GenerativeSearchReadinessWorkspaceController.php',
            );

        foreach (
            [
                '->update(',
                '->create(',
                '->delete(',
                'AiSeoProvider',
                'AiSeoSuggestionGenerationService',
                'AiSeoBulkApplicationService',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $query,
            );

            self::assertStringNotContainsString(
                $forbidden,
                $controller,
            );
        }
    }

    public function test_workspace_links_to_existing_entity_seo_and_crawler_policy_surfaces(): void
    {
        $page =
            $this->source(
                'resources/js/Pages/Admin/Seo/GenerativeSearch/Readiness.tsx',
            );

        self::assertStringContainsString(
            'admin.seo.entities.show',
            $page,
        );

        self::assertStringContainsString(
            'admin.seo.generative-search.crawlers.index',
            $page,
        );

        self::assertStringContainsString(
            'summary.truncated',
            $page,
        );
    }

    private function source(
        string $path,
    ): string {
        $source =
            file_get_contents(
                base_path(
                    $path,
                ),
            );

        self::assertIsString(
            $source,
            $path,
        );

        return $source;
    }
}
