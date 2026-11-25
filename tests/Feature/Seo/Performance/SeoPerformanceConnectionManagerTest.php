<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Models\Admin;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoPerformanceConnectionManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_provider_metadata_without_credentials_and_records_verification(): void
    {
        $admin = Admin::factory()->create();
        $manager = app(SeoPerformanceConnectionManager::class);

        $connection = $manager->register(
            SeoPerformanceProvider::SearchConsole,
            'Primary Search Console',
            'sc-domain:example.com',
            'https://example.com',
            'Asia/Dhaka',
            $admin,
        );

        self::assertSame(SeoPerformanceConnectionStatus::Configured, $connection->status);
        self::assertNull($connection->verified_at);
        self::assertSame($admin->getKey(), $connection->created_by_admin_id);
        self::assertContains(
            SeoPerformanceCapability::SearchQueries->value,
            $connection->capabilities,
        );
        self::assertNotContains(
            SeoPerformanceCapability::StructuredDataIssues->value,
            $connection->capabilities,
        );
        self::assertNotContains(
            SeoPerformanceCapability::GenerativeAiVisibility->value,
            $connection->capabilities,
        );

        $active = $manager->recordSuccessfulVerification($connection);

        self::assertSame(SeoPerformanceConnectionStatus::Active, $active->status);
        self::assertNotNull($active->verified_at);
    }

    public function test_it_refuses_invalid_property_urls(): void
    {
        $this->expectException(DomainException::class);

        app(SeoPerformanceConnectionManager::class)->register(
            SeoPerformanceProvider::SearchConsole,
            'Invalid URL',
            'sc-domain:example.com',
            'file:///etc/passwd',
            'UTC',
        );
    }
}
