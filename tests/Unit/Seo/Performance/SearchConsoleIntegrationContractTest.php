<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class SearchConsoleIntegrationContractTest extends TestCase
{
    public function test_o2_read_only_connection_contract_remains_secure_after_o3_search_analytics_import(): void
    {
        $root = dirname(__DIR__, 4);

        $token = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/SearchConsoleServiceAccountAccessTokenProvider.php',
        );
        $api = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/SearchConsoleApiClient.php',
        );
        $adapter = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Providers/SearchConsoleProviderAdapter.php',
        );
        $connectionMigration = file_get_contents(
            $root.'/database/migrations/2026_11_24_190000_create_seo_performance_connections_table.php',
        );

        self::assertIsString($token);
        self::assertIsString($api);
        self::assertIsString($adapter);
        self::assertIsString($connectionMigration);

        self::assertStringContainsString(
            'https://www.googleapis.com/auth/webmasters.readonly',
            $token,
        );
        self::assertStringContainsString(
            'https://oauth2.googleapis.com/token',
            $token,
        );
        self::assertStringContainsString(
            'https://www.googleapis.com/webmasters/v3/sites',
            $api,
        );
        self::assertStringContainsString('rawurlencode($propertyIdentifier)', $api);

        $propertyAccess = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Data/SearchConsolePropertyAccessData.php',
        );
        self::assertIsString($propertyAccess);
        self::assertStringContainsString('siteOwner', $propertyAccess);
        self::assertStringContainsString('siteFullUser', $propertyAccess);
        self::assertStringContainsString('siteRestrictedUser', $propertyAccess);

        self::assertStringContainsString('public function fetchPage(', $adapter);
        self::assertStringContainsString('$this->api->searchAnalytics(', $adapter);
        self::assertStringContainsString("'/searchAnalytics/query'", $api);
        self::assertStringContainsString("'dataState' => 'final'", $api);
        self::assertStringContainsString("'type' => 'web'", $api);

        self::assertStringNotContainsString('access_token', $connectionMigration);
        self::assertStringNotContainsString('refresh_token', $connectionMigration);
        self::assertStringNotContainsString('private_key', $connectionMigration);
    }
}
