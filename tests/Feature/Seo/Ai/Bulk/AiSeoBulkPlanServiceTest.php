<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkJobStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoBulkPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_is_dry_run_with_generate_and_skip_items(): void
    {
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

        $job =
            app(
                AiSeoBulkPlanService::class,
            )->create(
                targets: [
                    [
                        'entity_type' => 'product',
                        'entity_id' => $ready->id,
                    ],
                    [
                        'entity_type' => 'category',
                        'entity_id' => $fresh->id,
                    ],
                ],
                plannedByAdminId: 88,
            );

        self::assertSame(
            AiSeoBulkJobStatus::Planned,
            $job->status,
        );

        self::assertSame(
            1,
            $job->summary['generate'],
        );

        self::assertSame(
            1,
            $job->summary['skip'],
        );

        self::assertCount(
            2,
            $job->items,
        );

        self::assertDatabaseCount(
            'ai_seo_requests',
            1,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestions',
            0,
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
