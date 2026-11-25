<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkRetryService;
use App\Jobs\Seo\AiSeoBulkItemExecutionJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class AiSeoBulkRetryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_failed_only_requeues_failed_items(): void
    {
        Queue::fake();

        $first =
            Product::factory()
                ->create();

        $second =
            Product::factory()
                ->create();

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $first->id,
                ],
                [
                    'entity_type' => 'product',

                    'entity_id' => $second->id,
                ],
            ]);

        $items =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->orderBy(
                    'id',
                )
                ->get();

        $failed =
            $items[0];

        $completed =
            $items[1];

        $failed->update([
            'execution_status' => AiSeoBulkItemExecutionStatus::Failed,

            'execution_error_code' => 'test_failure',

            'execution_error_message' => 'Test failure.',

            'completed_at' => now(),
        ]);

        $completed->update([
            'execution_status' => AiSeoBulkItemExecutionStatus::Completed,

            'completed_at' => now(),
        ]);

        $plan->update([
            'status' => AiSeoBulkJobStatus::CompletedWithErrors,
        ]);

        $count =
            app(
                AiSeoBulkRetryService::class,
            )->retryFailed(
                $plan,
            );

        self::assertSame(
            1,
            $count,
        );

        $failed->refresh();
        $completed->refresh();
        $plan->refresh();

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Queued,
            $failed
                ->execution_status,
        );

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Completed,
            $completed
                ->execution_status,
        );

        self::assertSame(
            AiSeoBulkJobStatus::Running,
            $plan
                ->status,
        );

        Queue::assertPushed(
            AiSeoBulkItemExecutionJob::class,
            1,
        );
    }
}
