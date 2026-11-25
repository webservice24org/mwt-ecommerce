<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use App\Domain\Seo\Performance\Contracts\SeoPerformanceProviderAdapter;
use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Services\SeoPerformanceCapabilityRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SeoPerformanceIntegrationFoundationContractTest extends TestCase
{
    public function test_provider_capabilities_are_explicit_and_unsupported_roadmap_signals_are_not_invented(): void
    {
        $registry = new SeoPerformanceCapabilityRegistry;

        self::assertSame(
            [
                SeoPerformanceCapability::SearchQueries,
                SeoPerformanceCapability::SearchLandingPages,
            ],
            $registry->forProvider(SeoPerformanceProvider::SearchConsole),
        );
        self::assertSame(
            [SeoPerformanceCapability::AnalyticsLandingPages],
            $registry->forProvider(SeoPerformanceProvider::GoogleAnalytics),
        );

        foreach (SeoPerformanceProvider::cases() as $provider) {
            self::assertNotContains(
                SeoPerformanceCapability::StructuredDataIssues,
                $registry->forProvider($provider),
            );
            self::assertNotContains(
                SeoPerformanceCapability::GenerativeAiVisibility,
                $registry->forProvider($provider),
            );
        }
    }

    public function test_provider_contract_is_adapter_based_and_foundation_stores_no_oauth_secrets(): void
    {
        $reflection = new ReflectionClass(SeoPerformanceProviderAdapter::class);

        self::assertTrue($reflection->isInterface());
        self::assertNotNull($reflection->getMethod('verify'));
        self::assertNotNull($reflection->getMethod('fetchPage'));

        $projectRoot = dirname(__DIR__, 4);
        $connectionMigration = file_get_contents(
            $projectRoot.'/database/migrations/2026_11_24_190000_create_seo_performance_connections_table.php',
        );
        $contract = file_get_contents(
            $projectRoot.'/app/Domain/Seo/Performance/Contracts/SeoPerformanceProviderAdapter.php',
        );

        self::assertIsString($connectionMigration);
        self::assertIsString($contract);

        foreach ([
            'access_token',
            'refresh_token',
            'client_secret',
            'customer_name',
            'order_id',
            'payment',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $connectionMigration);
        }

        self::assertStringNotContainsString('Http::', $contract);
        self::assertStringNotContainsString('Google\\', $contract);
    }
}
