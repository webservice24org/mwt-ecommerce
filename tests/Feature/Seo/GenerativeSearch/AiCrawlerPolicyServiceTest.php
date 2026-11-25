<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiCrawlerPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_policy_preserves_existing_effective_open_crawl_behavior(): void
    {
        $policy =
            app(
                AiCrawlerPolicyService::class,
            )->get();

        self::assertTrue(
            $policy->allowOaiSearchBot,
        );

        self::assertTrue(
            $policy->allowGptBot,
        );

        self::assertTrue(
            $policy->allowGoogleExtended,
        );

        self::assertSame(
            1,
            $policy->version,
        );

        self::assertDatabaseCount(
            'ai_crawler_policies',
            1,
        );

        self::assertDatabaseCount(
            'ai_crawler_policy_events',
            0,
        );
    }

    public function test_save_is_versioned_audited_and_cache_coherent(): void
    {
        $service =
            app(
                AiCrawlerPolicyService::class,
            );

        $initial =
            $service->get();

        $saved =
            $service->save(
                allowOaiSearchBot: false,

                allowGptBot: true,

                allowGoogleExtended: false,

                expectedVersion: $initial->version,

                changedByAdminId: 701,
            );

        self::assertFalse(
            $saved->allowOaiSearchBot,
        );

        self::assertTrue(
            $saved->allowGptBot,
        );

        self::assertFalse(
            $saved->allowGoogleExtended,
        );

        self::assertSame(
            2,
            $saved->version,
        );

        $fresh =
            $service->get();

        self::assertSame(
            $saved->toArray(),
            $fresh->toArray(),
        );

        self::assertDatabaseHas(
            'ai_crawler_policy_events',
            [
                'version' => 2,

                'changed_by_admin_id' => 701,
            ],
        );
    }

    public function test_stale_version_is_rejected_without_overwriting_newer_policy(): void
    {
        $service =
            app(
                AiCrawlerPolicyService::class,
            );

        $initial =
            $service->get();

        $service->save(
            allowOaiSearchBot: false,

            allowGptBot: true,

            allowGoogleExtended: true,

            expectedVersion: $initial->version,
        );

        $this->expectException(
            DomainException::class,
        );

        try {
            $service->save(
                allowOaiSearchBot: true,

                allowGptBot: false,

                allowGoogleExtended: false,

                expectedVersion: $initial->version,
            );
        } finally {
            $current =
                $service->get();

            self::assertFalse(
                $current->allowOaiSearchBot,
            );

            self::assertTrue(
                $current->allowGptBot,
            );

            self::assertTrue(
                $current->allowGoogleExtended,
            );

            self::assertSame(
                2,
                $current->version,
            );

            self::assertDatabaseCount(
                'ai_crawler_policy_events',
                1,
            );
        }
    }

    public function test_noop_save_does_not_create_fake_history_or_increment_version(): void
    {
        $service =
            app(
                AiCrawlerPolicyService::class,
            );

        $initial =
            $service->get();

        $saved =
            $service->save(
                allowOaiSearchBot: true,

                allowGptBot: true,

                allowGoogleExtended: true,

                expectedVersion: $initial->version,

                changedByAdminId: 702,
            );

        self::assertSame(
            1,
            $saved->version,
        );

        self::assertDatabaseCount(
            'ai_crawler_policy_events',
            0,
        );
    }
}
