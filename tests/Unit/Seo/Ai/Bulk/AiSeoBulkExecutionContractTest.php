<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Jobs\Seo\AiSeoBulkItemExecutionJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoBulkExecutionContractTest extends TestCase
{
    public function test_execution_status_contract_is_explicit_and_terminal_states_are_locked(): void
    {
        self::assertSame(
            [
                'planned',
                'queued',
                'processing',
                'completed',
                'skipped',
                'drifted',
                'failed',
            ],
            array_map(
                static fn (
                    AiSeoBulkItemExecutionStatus $status,
                ): string => $status->value,
                AiSeoBulkItemExecutionStatus::cases(),
            ),
        );

        self::assertFalse(
            AiSeoBulkItemExecutionStatus::Planned
                ->terminal(),
        );

        self::assertFalse(
            AiSeoBulkItemExecutionStatus::Queued
                ->terminal(),
        );

        self::assertFalse(
            AiSeoBulkItemExecutionStatus::Processing
                ->terminal(),
        );

        self::assertTrue(
            AiSeoBulkItemExecutionStatus::Completed
                ->terminal(),
        );

        self::assertTrue(
            AiSeoBulkItemExecutionStatus::Skipped
                ->terminal(),
        );

        self::assertTrue(
            AiSeoBulkItemExecutionStatus::Drifted
                ->terminal(),
        );

        self::assertTrue(
            AiSeoBulkItemExecutionStatus::Failed
                ->terminal(),
        );
    }

    public function test_item_job_is_queued_and_unique_per_bulk_item(): void
    {
        $interfaces =
            class_implements(
                AiSeoBulkItemExecutionJob::class,
            );

        self::assertIsArray(
            $interfaces,
        );

        self::assertArrayHasKey(
            ShouldQueue::class,
            $interfaces,
        );

        self::assertArrayHasKey(
            ShouldBeUnique::class,
            $interfaces,
        );

        $job =
            new AiSeoBulkItemExecutionJob(
                42,
            );

        self::assertSame(
            'ai-seo-bulk-item:42',
            $job->uniqueId(),
        );

        self::assertGreaterThanOrEqual(
            1,
            $job->tries,
        );
    }

    public function test_k2_routes_support_execution_and_retry_without_legacy_direct_apply_aliases(): void
    {
        self::assertTrue(
            Route::has(
                'admin.seo.bulk.plans.show',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.bulk.plans.execute',
            ),
        );

        self::assertTrue(
            Route::has(
                'admin.seo.bulk.plans.retry-failed',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.apply',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.approve',
            ),
        );
    }

    public function test_bulk_execution_sources_use_existing_generation_pipeline_and_never_write_live_seo(): void
    {
        $source =
            file_get_contents(
                base_path(
                    'app/Domain/Seo/Ai/Bulk/Services/AiSeoBulkItemExecutionService.php',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            'AiSeoDraftRequestService',
            $source,
        );

        self::assertStringContainsString(
            'AiSeoSuggestionGenerationService',
            $source,
        );

        foreach (
            [
                'meta_title =',
                'meta_description =',
                'canonical_url =',
                'robots_index =',
                'EntitySeoRepository',
            ] as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source,
            );
        }
    }
}
