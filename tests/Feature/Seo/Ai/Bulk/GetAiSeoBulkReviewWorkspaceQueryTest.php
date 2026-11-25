<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkReviewWorkspaceQuery;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionReviewService;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetAiSeoBulkReviewWorkspaceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_combines_bulk_item_suggestions_review_state_and_freshness(): void
    {
        $fresh = Product::factory()->create([
            'name' => 'Fresh Bulk Review Product',
            'meta_title' => 'Original fresh title',
        ]);

        $stale = Product::factory()->create([
            'name' => 'Stale Bulk Review Product',
            'meta_title' => 'Original stale title',
            'description' => 'Original body.',
        ]);

        $plan = app(AiSeoBulkPlanService::class)->create([
            [
                'entity_type' => 'product',
                'entity_id' => $fresh->id,
            ],
            [
                'entity_type' => 'product',
                'entity_id' => $stale->id,
            ],
        ]);

        $freshSuggestion = $this->attachSuggestion(
            $plan->id,
            $fresh,
            'Fresh AI title',
        );

        $staleSuggestion = $this->attachSuggestion(
            $plan->id,
            $stale,
            'Stale AI title',
        );

        app(AiSeoSuggestionReviewService::class)->approve(
            suggestion: $freshSuggestion,
            reviewedByAdminId: 10,
            note: 'Approved before bulk review page.',
        );

        $stale->update([
            'description' => 'Changed after AI suggestion generation.',
        ]);

        $workspace = app(
            GetAiSeoBulkReviewWorkspaceQuery::class,
        )->handle($plan->id);

        self::assertSame(2, $workspace['summary']['total']);
        self::assertSame(1, $workspace['summary']['approved']);
        self::assertSame(1, $workspace['summary']['pending']);
        self::assertSame(1, $workspace['summary']['fresh']);
        self::assertSame(1, $workspace['summary']['stale']);

        $rows = collect($workspace['suggestions'])->keyBy('id');

        self::assertSame(
            'approved',
            $rows[$freshSuggestion->id]['review_status'],
        );

        self::assertFalse(
            $rows[$freshSuggestion->id]['freshness']['is_stale'],
        );

        self::assertTrue(
            $rows[$staleSuggestion->id]['freshness']['is_stale'],
        );

        self::assertFalse(
            $rows[$staleSuggestion->id]['can_approve'],
        );
    }

    private function attachSuggestion(
        int $jobId,
        Product $product,
        string $value,
    ): AiSeoSuggestion {
        $snapshot = app(
            AiSeoEntitySnapshotBuilder::class,
        )
            ->build(
                AiSeoEntityType::Product,
                $product->id,
            )
            ->toArray();

        $request = AiSeoRequest::query()->create([
            'entity_type' => AiSeoEntityType::Product,
            'entity_id' => $product->id,
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

        $suggestion = AiSeoSuggestion::query()->create([
            'ai_seo_request_id' => $request->id,
            'field' => AiSeoSuggestionField::MetaTitle,
            'value' => $value,
            'rationale' => 'Bulk review test rationale.',
            'position' => 0,
        ]);

        AiSeoBulkJobItem::query()
            ->where('ai_seo_bulk_job_id', $jobId)
            ->where('entity_type', AiSeoEntityType::Product->value)
            ->where('entity_id', $product->id)
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Completed,
                'ai_seo_request_id' => $request->id,
                'completed_at' => now(),
            ]);

        return $suggestion->refresh();
    }
}
