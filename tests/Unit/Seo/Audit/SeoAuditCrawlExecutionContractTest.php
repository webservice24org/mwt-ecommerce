<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use PHPUnit\Framework\TestCase;

final class SeoAuditCrawlExecutionContractTest extends TestCase
{
    public function test_n3_is_a_bounded_observation_pipeline_not_a_rule_runner_or_queue(): void
    {
        $projectRoot = dirname(
            __DIR__,
            4,
        );

        $fetcher = file_get_contents(
            $projectRoot
            .'/app/Domain/Seo/Audit/Services/SeoAuditHttpPageFetcher.php',
        );

        self::assertIsString($fetcher);
        self::assertStringContainsString(
            'MAX_BODY_BYTES = 2000000',
            $fetcher,
        );
        self::assertStringContainsString(
            'withoutRedirecting()',
            $fetcher,
        );
        self::assertStringContainsString(
            "'stream' => true",
            $fetcher,
        );
        self::assertStringContainsString(
            'CONNECT_TIMEOUT_SECONDS = 5',
            $fetcher,
        );
        self::assertStringContainsString(
            'TIMEOUT_SECONDS = 10',
            $fetcher,
        );

        $crawler = file_get_contents(
            $projectRoot
            .'/app/Domain/Seo/Audit/Services/SeoAuditBoundedCrawler.php',
        );

        self::assertIsString($crawler);
        self::assertStringContainsString(
            'MAX_FETCHES_PER_EXECUTION = 5000',
            $crawler,
        );
        self::assertStringContainsString(
            'MAX_SEEDS = 50',
            $crawler,
        );
        self::assertStringNotContainsString(
            'recordFinding(',
            $crawler,
        );
        self::assertStringNotContainsString(
            'dispatch(',
            $crawler,
        );
        self::assertStringNotContainsString(
            'ShouldQueue',
            $crawler,
        );

        $parser = file_get_contents(
            $projectRoot
            .'/app/Domain/Seo/Audit/Services/SeoAuditHtmlObservationParser.php',
        );

        self::assertIsString($parser);
        self::assertStringContainsString(
            'DOMDocument',
            $parser,
        );
        self::assertStringContainsString(
            'LIBXML_NONET',
            $parser,
        );

        $migration = file_get_contents(
            $projectRoot
            .'/database/migrations/2026_11_24_130000_create_seo_audit_page_images_table.php',
        );

        self::assertIsString($migration);
        self::assertStringContainsString(
            "'sapi_run_page_obs_uidx'",
            $migration,
        );
    }
}
