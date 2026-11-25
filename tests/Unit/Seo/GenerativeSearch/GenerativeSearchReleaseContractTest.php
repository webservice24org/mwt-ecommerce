<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchFindingSeverity;
use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchReadinessStatus;
use App\Domain\Seo\GenerativeSearch\GenerativeSearchReadinessPolicy;
use App\Domain\Seo\GenerativeSearch\Queries\GetGenerativeSearchReadinessWorkspaceQuery;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class GenerativeSearchReleaseContractTest extends TestCase
{
    public function test_l1_readiness_contract_is_versioned_deterministic_and_non_numeric(): void
    {
        self::assertSame(
            'generative-search-readiness-v1',
            GenerativeSearchReadinessPolicy::VERSION,
        );

        self::assertSame(
            [
                'product',
                'category',
                'brand',
                'page',
            ],
            GenerativeSearchReadinessPolicy::recognizedEntityTypes(),
        );

        self::assertSame(
            [
                'ready',
                'needs_work',
                'blocked',
            ],
            array_map(
                static fn (
                    GenerativeSearchReadinessStatus $status,
                ): string => $status->value,
                GenerativeSearchReadinessStatus::cases(),
            ),
        );

        self::assertSame(
            [
                'blocker',
                'warning',
                'info',
            ],
            array_map(
                static fn (
                    GenerativeSearchFindingSeverity $severity,
                ): string => $severity->value,
                GenerativeSearchFindingSeverity::cases(),
            ),
        );

        $analyzer =
            $this->source(
                'app/Domain/Seo/GenerativeSearch/Services/GenerativeSearchReadinessAnalyzer.php',
            );

        self::assertStringNotContainsString(
            'AiSeoProvider',
            $analyzer,
        );

        self::assertStringNotContainsString(
            "'score' =>",
            $analyzer,
        );

        self::assertStringContainsString(
            'indexing_blocked',
            $analyzer,
        );
    }

    public function test_l2_keeps_search_training_and_google_extended_controls_separate(): void
    {
        $appender =
            $this->source(
                'app/Domain/Seo/GenerativeSearch/Services/AiCrawlerRobotsPolicyAppender.php',
            );

        self::assertStringContainsString(
            'OAI-SearchBot',
            $appender,
        );

        self::assertStringContainsString(
            'GPTBot',
            $appender,
        );

        self::assertStringContainsString(
            'Google-Extended',
            $appender,
        );

        self::assertStringNotContainsString(
            'ChatGPT-User',
            $appender,
        );

        self::assertStringNotContainsString(
            '__usage_ai-training',
            $appender,
        );

        self::assertStringNotContainsString(
            'User-agent: OAI-SearchBot\\nAllow: /',
            $appender,
        );

        self::assertStringNotContainsString(
            'User-agent: GPTBot\\nAllow: /',
            $appender,
        );

        self::assertStringNotContainsString(
            'User-agent: Google-Extended\\nAllow: /',
            $appender,
        );
    }

    public function test_l3_visible_answerability_has_no_hidden_ai_copy_or_synthetic_faq_contract(): void
    {
        foreach (
            [
                'resources/js/Components/Frontend/Seo/EntityContextLinks.tsx',
                'resources/js/Components/Frontend/Seo/ProductEntityFacts.tsx',
            ] as $path
        ) {
            $source =
                $this->source(
                    $path,
                );

            foreach (
                [
                    'sr-only',
                    'display:none',
                    'data-ai',
                    'FAQPage',
                    'application/ld+json',
                ] as $forbidden
            ) {
                self::assertStringNotContainsString(
                    $forbidden,
                    $source,
                    $path,
                );
            }
        }

        $product =
            $this->source(
                'resources/js/Components/Frontend/Seo/ProductEntityFacts.tsx',
            );

        self::assertStringContainsString(
            '<dl',
            $product,
        );

        self::assertStringContainsString(
            '/brand/${product.brand.slug}',
            $product,
        );

        self::assertStringContainsString(
            '/category/${category.slug}',
            $product,
        );
    }

    public function test_l4_workspace_is_read_only_bounded_and_does_not_create_an_ai_ranking_score(): void
    {
        self::assertSame(
            250,
            GetGenerativeSearchReadinessWorkspaceQuery::SCAN_LIMIT,
        );

        $query =
            $this->source(
                'app/Domain/Seo/GenerativeSearch/Queries/GetGenerativeSearchReadinessWorkspaceQuery.php',
            );

        $controller =
            $this->source(
                'app/Http/Controllers/Admin/Seo/GenerativeSearchReadinessWorkspaceController.php',
            );

        $page =
            $this->source(
                'resources/js/Pages/Admin/Seo/GenerativeSearch/Readiness.tsx',
            );

        foreach (
            [
                'AiSeoProvider',
                'AiSeoSuggestionGenerationService',
                'AiSeoBulkApplicationService',
                '->update(',
                '->delete(',
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

    public function test_l_release_route_surface_contains_only_the_expected_public_and_admin_controls(): void
    {
        foreach (
            [
                'robots.txt',
                'admin.seo.generative-search.crawlers.index',
                'admin.seo.generative-search.crawlers.update',
                'admin.seo.generative-search.readiness',
            ] as $routeName
        ) {
            self::assertTrue(
                Route::has(
                    $routeName,
                ),
                $routeName,
            );
        }

        foreach (
            [
                'admin.seo.generative-search.generate',
                'admin.seo.generative-search.apply',
                'admin.seo.generative-search.auto-optimize',
                'admin.seo.generative-search.readiness.store',
                'admin.seo.generative-search.readiness.update',
            ] as $routeName
        ) {
            self::assertFalse(
                Route::has(
                    $routeName,
                ),
                $routeName,
            );
        }
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
