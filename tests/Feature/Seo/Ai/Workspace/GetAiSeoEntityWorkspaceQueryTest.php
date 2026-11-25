<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Domain\Seo\Ai\Workspace\Queries\GetAiSeoEntityWorkspaceQuery;
use App\Models\AiSeoAnalysis;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\AiSeoSuggestionReview;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetAiSeoEntityWorkspaceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_combines_current_entity_analysis_history_and_reviewed_suggestions(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Workspace Product',

                    'slug' => 'workspace-product',

                    'meta_title' => 'Existing workspace title',

                    'description' => 'Stable factual workspace content.',
                ]);

        $snapshot =
            app(
                AiSeoEntitySnapshotBuilder::class,
            )
                ->build(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ->toArray();

        $snapshotHash =
            app(
                AiSeoSnapshotHasher::class,
            )->hash(
                $snapshot,
            );

        $now =
            now();

        $analysis =
            AiSeoAnalysis::query()
                ->create([
                    'entity_type' => AiSeoEntityType::Product,

                    'entity_id' => $product->id,

                    'analysis_version' => 'ai-seo-analysis-v1',

                    'snapshot_schema_version' => 'ai-seo-entity-snapshot-v1',

                    'snapshot_hash' => $snapshotHash,

                    'analysis_input_hash' => str_repeat(
                        'b',
                        64,
                    ),

                    'source_snapshot' => $snapshot,

                    'resolved_seo' => [
                    'title' => 'Workspace Product | MWT',
                    ],

                    'score' => 82,

                    'grade' => AiSeoAnalysisGrade::Good,

                    'breakdown' => [
                    'search_metadata' => [
                        'points' => 40,

                        'max_points' => 50,
                    ],
                    ],

                    'rules' => [],

                    'findings' => [
                    [
                        'id' => 'social_image_missing',

                        'category' => 'social_coverage',

                        'severity' => 'info',

                        'title' => 'Social image missing',

                        'message' => 'No image is resolved.',

                        'recommendation' => 'Configure an accurate image.',
                    ],
                    ],

                    'analyzed_by_admin_id' => null,

                    'analyzed_at' => $now,

                    'created_at' => $now,
                ]);

        $request =
            AiSeoRequest::query()
                ->create([
                    'entity_type' => AiSeoEntityType::Product,

                    'entity_id' => $product->id,

                    'action' => AiSeoAction::GenerateMetadataSuggestions,

                    'status' => AiSeoRequestStatus::Completed,

                    'requested_by_admin_id' => 10,

                    'input_snapshot' => [
                        'entity_snapshot' => $snapshot,
                    ],

                    'input_hash' => str_repeat(
                        'c',
                        64,
                    ),

                    'provider' => 'test-provider',

                    'model_name' => 'test-model',

                    'prompt_version' => 'ai-seo-metadata-v1',

                    'completed_at' => $now,
                ]);

        $suggestion =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => 'Suggested workspace title',

                    'rationale' => 'More descriptive.',

                    'position' => 0,
                ]);

        AiSeoSuggestionReview::query()
            ->create([
                'ai_seo_suggestion_id' => $suggestion->id,

                'decision' => AiSeoSuggestionReviewDecision::Approved,

                'reviewed_by_admin_id' => 11,

                'note' => 'Looks good.',

                'source_snapshot_hash' => $snapshotHash,

                'current_snapshot_hash' => $snapshotHash,

                'freshness_state' => 'fresh',

                'reviewed_at' => $now,

                'created_at' => $now,
            ]);

        $workspace =
            app(
                GetAiSeoEntityWorkspaceQuery::class,
            )->handle(
                AiSeoEntityType::Product,
                $product->id,
            );

        self::assertSame(
            'Workspace Product',
            $workspace['entity']['name'],
        );

        self::assertSame(
            82,
            $workspace['summary']['latest_score'],
        );

        self::assertFalse(
            $workspace['summary']['analysis_stale'],
        );

        self::assertSame(
            1,
            $workspace['summary']['finding_count'],
        );

        self::assertSame(
            1,
            $workspace['summary']['suggestion_count'],
        );

        self::assertSame(
            1,
            $workspace['summary']['approved_suggestions'],
        );

        self::assertSame(
            0,
            $workspace['summary']['stale_suggestions'],
        );

        self::assertSame(
            $analysis->id,
            $workspace['latest_analysis']['id'],
        );

        self::assertSame(
            'Existing workspace title',
            $workspace['suggestions'][0]['existing_value'],
        );

        self::assertSame(
            'approved',
            $workspace['suggestions'][0]['review_status'],
        );

        self::assertFalse(
            $workspace['suggestions'][0]['freshness']['is_stale'],
        );

        self::assertSame(
            $request->id,
            $workspace['requests'][0]['id'],
        );
    }

    public function test_workspace_marks_analysis_and_suggestions_stale_after_entity_changes(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Stale Workspace Product',

                    'slug' => 'stale-workspace-product',

                    'description' => 'Original content.',
                ]);

        $snapshot =
            app(
                AiSeoEntitySnapshotBuilder::class,
            )
                ->build(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ->toArray();

        $snapshotHash =
            app(
                AiSeoSnapshotHasher::class,
            )->hash(
                $snapshot,
            );

        $now =
            now();

        AiSeoAnalysis::query()
            ->create([
                'entity_type' => AiSeoEntityType::Product,

                'entity_id' => $product->id,

                'analysis_version' => 'ai-seo-analysis-v1',

                'snapshot_schema_version' => 'ai-seo-entity-snapshot-v1',

                'snapshot_hash' => $snapshotHash,

                'analysis_input_hash' => str_repeat(
                    'd',
                    64,
                ),

                'source_snapshot' => $snapshot,

                'resolved_seo' => [],

                'score' => 60,

                'grade' => AiSeoAnalysisGrade::NeedsWork,

                'breakdown' => [],

                'rules' => [],

                'findings' => [],

                'analyzed_by_admin_id' => null,

                'analyzed_at' => $now,

                'created_at' => $now,
            ]);

        $request =
            AiSeoRequest::query()
                ->create([
                    'entity_type' => AiSeoEntityType::Product,

                    'entity_id' => $product->id,

                    'action' => AiSeoAction::GenerateMetadataSuggestions,

                    'status' => AiSeoRequestStatus::Completed,

                    'requested_by_admin_id' => null,

                    'input_snapshot' => [
                        'entity_snapshot' => $snapshot,
                    ],

                    'input_hash' => str_repeat(
                        'e',
                        64,
                    ),

                    'provider' => 'test-provider',

                    'model_name' => 'test-model',

                    'prompt_version' => 'ai-seo-metadata-v1',

                    'completed_at' => $now,
                ]);

        AiSeoSuggestion::query()
            ->create([
                'ai_seo_request_id' => $request->id,

                'field' => AiSeoSuggestionField::MetaDescription,

                'value' => 'Suggested description.',

                'rationale' => null,

                'position' => 0,
            ]);

        $product->update([
            'description' => 'Changed after analysis and generation.',
        ]);

        $workspace =
            app(
                GetAiSeoEntityWorkspaceQuery::class,
            )->handle(
                AiSeoEntityType::Product,
                $product->id,
            );

        self::assertTrue(
            $workspace['summary']['analysis_stale'],
        );

        self::assertSame(
            1,
            $workspace['summary']['stale_suggestions'],
        );

        self::assertTrue(
            $workspace['suggestions'][0]['freshness']['is_stale'],
        );

        self::assertFalse(
            $workspace['suggestions'][0]['can_approve'],
        );
    }
}
