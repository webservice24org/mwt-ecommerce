<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class SearchConsoleSearchAnalyticsImportContractTest extends TestCase
{
    public function test_o3_is_bounded_finalized_and_does_not_add_queue_or_analytics_behavior(): void
    {
        $root = dirname(__DIR__, 4);

        $adapter = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Providers/SearchConsoleProviderAdapter.php',
        );
        $api = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/SearchConsoleApiClient.php',
        );
        $writer = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/SearchConsolePerformancePageWriter.php',
        );

        self::assertIsString($adapter);
        self::assertIsString($api);
        self::assertIsString($writer);

        self::assertStringContainsString('MAX_PAGE_SIZE = 1000', $adapter);
        self::assertStringContainsString('MAX_ROWS_PER_DIMENSION = 25000', $adapter);
        self::assertStringContainsString('[\'date\', $dimension === SearchConsolePerformanceDimension::Query ? \'query\' : \'page\']', $adapter);
        self::assertStringContainsString("'dataState' => 'final'", $api);
        self::assertStringContainsString("'type' => 'web'", $api);
        self::assertStringContainsString("'/searchAnalytics/query'", $api);
        self::assertStringContainsString('rawurlencode($propertyIdentifier)', $api);
        self::assertStringContainsString('lockForUpdate()', $writer);
        self::assertStringContainsString('upsert(', $writer);

        self::assertStringNotContainsString('ShouldQueue', $adapter.$api.$writer);
        self::assertStringNotContainsString('GoogleAnalytics', $adapter.$api.$writer);
        self::assertStringNotContainsString('structured_data_issues', $adapter.$api.$writer);
        self::assertStringNotContainsString('generative_ai_visibility', $adapter.$api.$writer);
    }
}
