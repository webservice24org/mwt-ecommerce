<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class GoogleAnalyticsIntegrationContractTest extends TestCase
{
    public function test_o6_uses_read_only_data_api_and_keeps_landing_analytics_bounded(): void
    {
        $root = dirname(__DIR__, 4);

        $tokens = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/GoogleAnalyticsServiceAccountAccessTokenProvider.php',
        );
        $api = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/GoogleAnalyticsDataApiClient.php',
        );
        $adapter = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Providers/GoogleAnalyticsProviderAdapter.php',
        );
        $migration = file_get_contents(
            $root.'/database/migrations/2026_11_24_203000_create_seo_google_analytics_landing_page_rows_table.php',
        );

        self::assertIsString($tokens);
        self::assertIsString($api);
        self::assertIsString($adapter);
        self::assertIsString($migration);

        self::assertStringContainsString(
            'https://www.googleapis.com/auth/analytics.readonly',
            $tokens,
        );
        self::assertStringContainsString(
            'https://analyticsdata.googleapis.com/v1beta/properties',
            $api,
        );
        self::assertStringContainsString("['name' => 'landingPage']", $api);
        self::assertStringContainsString("['name' => 'sessions']", $api);
        self::assertStringContainsString("['name' => 'engagedSessions']", $api);
        self::assertStringContainsString("['name' => 'keyEvents']", $api);
        self::assertStringContainsString("['name' => 'totalRevenue']", $api);
        self::assertStringContainsString('MAX_PAGE_SIZE = 1000', $adapter);
        self::assertStringContainsString('MAX_ROWS_PER_SYNC = 25_000', $adapter);
        self::assertStringContainsString('SeoPerformanceSyncScope::LandingAnalytics', $adapter);

        self::assertStringNotContainsString('access_token', strtolower($migration));
        self::assertStringNotContainsString('refresh_token', strtolower($migration));
        self::assertStringNotContainsString('private_key', strtolower($migration));
        self::assertStringNotContainsString('customer', strtolower($migration));
        self::assertStringNotContainsString('order_id', strtolower($migration));
    }
}
