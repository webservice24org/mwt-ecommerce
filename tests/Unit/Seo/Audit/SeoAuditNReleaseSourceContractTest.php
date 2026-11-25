<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use PHPUnit\Framework\TestCase;

final class SeoAuditNReleaseSourceContractTest extends TestCase
{
    public function test_n10_integrates_internal_link_finalization_without_automatic_content_mutation(): void
    {
        $root = dirname(__DIR__, 4);

        $orchestrator = (string) file_get_contents(
            $root.'/app/Domain/Seo/Audit/Services/SeoAuditQueuedOrchestrator.php',
        );
        $job = (string) file_get_contents(
            $root.'/app/Jobs/Seo/Audit/ProcessSeoAuditRun.php',
        );
        $builder = (string) file_get_contents(
            $root.'/app/Domain/Seo/Audit/Services/SeoAuditInternalLinkOpportunityBuilder.php',
        );
        $review = (string) file_get_contents(
            $root.'/app/Domain/Seo/Audit/Services/SeoAuditInternalLinkReviewService.php',
        );
        $controller = (string) file_get_contents(
            $root.'/app/Http/Controllers/Admin/Seo/SeoAuditInternalLinkReviewController.php',
        );

        self::assertStringContainsString(
            'SeoAuditInternalLinkOpportunityBuilder $internalLinks',
            $orchestrator,
        );
        self::assertStringContainsString(
            'ensureInternalLinksBuilt',
            $orchestrator,
        );
        self::assertStringContainsString(
            "'internal_links_built_at'",
            $orchestrator,
        );
        self::assertStringContainsString(
            '$this->internalLinks->rebuild($run);',
            $orchestrator,
        );
        self::assertStringContainsString(
            "getAttribute('internal_links_built_at') !== null",
            $job,
        );

        $complete = strpos(
            $orchestrator,
            '$completed = $this->lifecycle->complete(',
        );
        $build = strpos(
            $orchestrator,
            '$completed = $this->ensureInternalLinksBuilt($completed);',
        );

        self::assertIsInt($complete);
        self::assertIsInt($build);
        self::assertTrue($complete < $build);

        foreach ([
            'MAX_TARGETS_PER_RUN = 250',
            'MAX_SOURCE_CANDIDATES = 1000',
            'MAX_OPPORTUNITIES_PER_RUN = 500',
            'SeoAuditInternalLinkReviewState::Pending',
        ] as $required) {
            self::assertStringContainsString(
                $required,
                $builder,
            );
        }

        self::assertStringContainsString(
            'lockForUpdate()',
            $review,
        );
        self::assertStringContainsString(
            'SeoAuditRunStatus::Completed',
            $review,
        );
        self::assertStringContainsString(
            "'viewAny'",
            $controller,
        );

        foreach ([
            'Product::query()->update',
            'Category::query()->update',
            'Page::query()->update',
            'Http::',
            'OpenAI',
            'SeoAiProvider',
        ] as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden,
                $orchestrator.$builder.$review.$controller,
            );
        }
    }
}
