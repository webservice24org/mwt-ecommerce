<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiGovernanceAuditWorkspaceContractTest extends TestCase
{
    #[Test]
    public function workspace_is_read_only_bounded_and_does_not_expose_raw_ai_input(): void
    {
        $query = file_get_contents(app_path('Domain/Seo/AiGovernance/Queries/GetSeoAiGovernanceAuditWorkspaceQuery.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/Seo/SeoAiGovernanceAuditController.php'));
        $page = file_get_contents(resource_path('js/Pages/Admin/Seo/AiGovernance/Index.tsx'));

        self::assertIsString($query);
        self::assertIsString($controller);
        self::assertIsString($page);

        self::assertStringContainsString('private const RUNS_PER_PAGE = 20;', $query);
        self::assertStringContainsString('private const ATTEMPT_LIMIT = 50;', $query);
        self::assertStringContainsString('private const EVENT_LIMIT = 100;', $query);
        self::assertStringNotContainsString("'input_payload'", $query);
        self::assertStringNotContainsString('SeoAiGovernanceManager', $controller);
        self::assertStringNotContainsString('approve(', $controller);
        self::assertStringNotContainsString('markPublished(', $controller);
        self::assertStringContainsString('Read-only AI governance audit', $query);
        self::assertStringContainsString('Raw AI input', $page);
        self::assertStringNotContainsString('router.post(', $page);
        self::assertStringNotContainsString('router.put(', $page);
        self::assertStringNotContainsString('router.delete(', $page);
    }
}
