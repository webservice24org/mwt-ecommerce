<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use PHPUnit\Framework\TestCase;

final class SeoAuditAdminWorkspaceContractTest extends TestCase
{
    public function test_n7_is_admin_operational_but_does_not_jump_to_automation_or_ai(): void
    {
        $projectRoot = dirname(__DIR__, 4);

        $page = (string) file_get_contents(
            $projectRoot.'/resources/js/Pages/Admin/Seo/Audit/Index.tsx',
        );
        $query = (string) file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Queries/GetSeoAuditAdminWorkspaceQuery.php',
        );
        $starter = (string) file_get_contents(
            $projectRoot.'/app/Domain/Seo/Audit/Services/SeoAuditAdminRunStarter.php',
        );

        self::assertStringContainsString(
            "route('admin.seo.audits.runs.store')",
            $page,
        );
        self::assertStringContainsString(
            'router.post(',
            $page,
        );
        self::assertStringContainsString(
            'FINDINGS_PER_PAGE = 25',
            $query,
        );
        self::assertStringContainsString(
            'RUNS_PER_PAGE = 15',
            $query,
        );
        self::assertStringContainsString(
            'SeoAuditRunStatus::Requested->value',
            $starter,
        );
        self::assertStringContainsString(
            'SeoAuditRunStatus::Running->value',
            $starter,
        );

        self::assertStringNotContainsString('schedule(', $page);
        self::assertStringNotContainsString('router.delete(', $page);
        self::assertStringNotContainsString('router.put(', $page);
        self::assertStringNotContainsString('OpenAI', $query);
        self::assertStringNotContainsString('Gemini', $query);
        self::assertStringNotContainsString('Anthropic', $query);
    }
}
