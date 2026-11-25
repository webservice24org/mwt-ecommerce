<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use PHPUnit\Framework\TestCase;

final class SeoAuditInternalLinkReviewWorkspaceContractTest extends TestCase
{
    public function test_n9_is_review_only_authorized_and_does_not_apply_storefront_content(): void
    {
        $root = dirname(__DIR__, 4);

        $service = (string) file_get_contents(
            $root.'/app/Domain/Seo/Audit/Services/SeoAuditInternalLinkReviewService.php',
        );
        $controller = (string) file_get_contents(
            $root.'/app/Http/Controllers/Admin/Seo/SeoAuditInternalLinkReviewController.php',
        );
        $page = (string) file_get_contents(
            $root.'/resources/js/Pages/Admin/Seo/InternalLinks/Index.tsx',
        );

        self::assertStringContainsString(
            'lockForUpdate()',
            $service,
        );
        self::assertStringContainsString(
            'SeoAuditRunStatus::Completed',
            $service,
        );
        self::assertStringContainsString(
            '!== SeoAuditInternalLinkReviewState::Pending',
            $service,
        );
        self::assertStringContainsString(
            "'reviewed_by_admin_id'",
            $service,
        );
        self::assertStringContainsString(
            "'reviewed_at'",
            $service,
        );
        self::assertStringContainsString(
            "'viewAny'",
            $controller,
        );
        self::assertStringContainsString(
            'router.patch(',
            $page,
        );
        self::assertStringContainsString(
            'never edits storefront content automatically',
            $page,
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
                $service.$controller,
            );
        }
    }
}
