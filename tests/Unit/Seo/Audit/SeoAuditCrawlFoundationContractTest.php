<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use PHPUnit\Framework\TestCase;

final class SeoAuditCrawlFoundationContractTest extends TestCase
{
    public function test_n2_is_a_bounded_inventory_and_graph_foundation_not_a_crawler_or_rule_runner(): void
    {
        $projectRoot =
            dirname(
                __DIR__,
                4,
            );

        $recorder =
            file_get_contents(
                $projectRoot
                .'/app/Domain/Seo/Audit/Services/SeoAuditCrawlInventoryRecorder.php',
            );

        self::assertIsString($recorder);

        self::assertStringContainsString(
            'MAX_PAGES_PER_RUN = 5000',
            $recorder,
        );

        self::assertStringContainsString(
            'MAX_LINKS_PER_RUN = 50000',
            $recorder,
        );

        self::assertStringContainsString(
            'MAX_DEPTH = 12',
            $recorder,
        );

        self::assertStringNotContainsString(
            'recordFinding(',
            $recorder,
        );

        self::assertStringNotContainsString(
            'Http::',
            $recorder,
        );

        self::assertStringNotContainsString(
            'dispatch(',
            $recorder,
        );

        self::assertStringNotContainsString(
            'ShouldQueue',
            $recorder,
        );

        $pageMigration =
            file_get_contents(
                $projectRoot
                .'/database/migrations/2026_11_24_123000_create_seo_audit_crawl_pages_table.php',
            );

        self::assertIsString(
            $pageMigration,
        );

        self::assertStringContainsString(
            "'sacp_run_url_uidx'",
            $pageMigration,
        );

        $linkMigration =
            file_get_contents(
                $projectRoot
                .'/database/migrations/2026_11_24_123100_create_seo_audit_internal_links_table.php',
            );

        self::assertIsString(
            $linkMigration,
        );

        self::assertStringContainsString(
            "'sail_run_edge_uidx'",
            $linkMigration,
        );
    }
}
