<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkDispatchService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Jobs\Seo\AiSeoBulkItemExecutionJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class AiSeoBulkDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_queues_generate_items_and_terminally_skips_fresh_plan_items(): void
    {
        Queue::fake();

        $ready =
            Product::factory()
                ->create();

        $fresh =
            Category::factory()
                ->create();

        $this->completedRequest(
            AiSeoEntityType::Category,
            $fresh->id,
        );

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $ready->id,
                ],
                [
                    'entity_type' => 'category',

                    'entity_id' => $fresh->id,
                ],
            ]);

        $result =
            app(
                AiSeoBulkDispatchService::class,
            )->dispatch(
                $plan,
            );

        self::assertSame(
            AiSeoBulkJobStatus::Running,
            $result->status,
        );

        $items =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->get()
                ->keyBy(
                    static fn (
                        AiSeoBulkJobItem $item,
                    ): string => $item
                        ->entity_type
                        ->value
                        .':'
                        .$item
                            ->entity_id,
                );

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Queued,
            $items[
                'product:'
                .$ready->id
            ]
                ->execution_status,
        );

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Skipped,
            $items[
                'category:'
                .$fresh->id
            ]
                ->execution_status,
        );

        Queue::assertPushed(
            AiSeoBulkItemExecutionJob::class,
            1,
        );
    }

    private function completedRequest(
        AiSeoEntityType $entityType,
        int $entityId,
    ): AiSeoRequest {
        $snapshot =
            app(
                AiSeoEntitySnapshotBuilder::class,
            )
                ->build(
                    $entityType,
                    $entityId,
                )
                ->toArray();

        return AiSeoRequest::query()
            ->create([
                'entity_type' => $entityType,

                'entity_id' => $entityId,

                'action' => AiSeoAction::GenerateMetadataSuggestions,

                'status' => AiSeoRequestStatus::Completed,

                'requested_by_admin_id' => null,

                'input_snapshot' => [
                    'entity_snapshot' => $snapshot,
                ],

                'input_hash' => app(
                    AiSeoSnapshotHasher::class,
                )->hash([
                    'entity_snapshot' => $snapshot,
                ]),

                'provider' => 'test-provider',

                'model_name' => 'test-model',

                'prompt_version' => 'ai-seo-metadata-v1',

                'completed_at' => now(),
            ]);
    }
}
