<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Domain\Seo\Ai\Workspace\Queries\GetAiSeoSuggestionsInboxQuery;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\AiSeoSuggestionReview;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetAiSeoSuggestionsInboxQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_returns_summary_filters_snapshot_values_and_freshness(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Inbox Product',

                    'slug' => 'inbox-product',

                    'meta_title' => 'Existing title',

                    'description' => 'Stable factual content.',
                ]);

        $request =
            $this->requestFor(
                $product,
                'openai',
            );

        $title =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => 'Improved Inbox Product Title',

                    'rationale' => 'More descriptive while staying factual.',

                    'position' => 0,
                ]);

        $description =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaDescription,

                    'value' => 'A useful factual description for the inbox product.',

                    'rationale' => 'Adds missing descriptive metadata.',

                    'position' => 1,
                ]);

        $now =
            now();

        AiSeoSuggestionReview::query()
            ->create([
                'ai_seo_suggestion_id' => $description->id,

                'decision' => AiSeoSuggestionReviewDecision::Rejected,

                'reviewed_by_admin_id' => 12,

                'note' => null,

                'source_snapshot_hash' => str_repeat(
                    'a',
                    64,
                ),

                'current_snapshot_hash' => str_repeat(
                    'a',
                    64,
                ),

                'freshness_state' => 'fresh',

                'reviewed_at' => $now,

                'created_at' => $now,
            ]);

        $inbox =
            app(
                GetAiSeoSuggestionsInboxQuery::class,
            )->handle();

        self::assertSame(
            2,
            $inbox['summary']['total'],
        );

        self::assertSame(
            1,
            $inbox['summary']['pending'],
        );

        self::assertSame(
            0,
            $inbox['summary']['approved'],
        );

        self::assertSame(
            1,
            $inbox['summary']['rejected'],
        );

        self::assertCount(
            2,
            $inbox['items'],
        );

        $titleItem =
            collect(
                $inbox['items'],
            )->firstWhere(
                'id',
                $title->id,
            );

        self::assertIsArray(
            $titleItem,
        );

        self::assertSame(
            'Inbox Product',
            $titleItem[
                'entity_name'
            ],
        );

        self::assertSame(
            'Existing title',
            $titleItem[
                'existing_value'
            ],
        );

        self::assertSame(
            'pending',
            $titleItem[
                'review_status'
            ],
        );

        self::assertFalse(
            $titleItem[
                'freshness'
            ]['is_stale'],
        );

        $filtered =
            app(
                GetAiSeoSuggestionsInboxQuery::class,
            )->handle(
                search: 'Inbox Product',

                entityType: 'product',

                field: 'meta_title',

                provider: 'openai',

                reviewStatus: 'pending',
            );

        self::assertCount(
            1,
            $filtered[
                'items'
            ],
        );

        self::assertSame(
            $title->id,
            $filtered[
                'items'
            ][0]['id'],
        );
    }

    private function requestFor(
        Product $product,
        string $provider,
    ): AiSeoRequest {
        $snapshot =
            app(
                AiSeoEntitySnapshotBuilder::class,
            )
                ->build(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ->toArray();

        return AiSeoRequest::query()
            ->create([
                'entity_type' => AiSeoEntityType::Product,

                'entity_id' => $product->id,

                'action' => AiSeoAction::GenerateMetadataSuggestions,

                'status' => AiSeoRequestStatus::Completed,

                'requested_by_admin_id' => 10,

                'input_snapshot' => [
                    'entity_snapshot' => $snapshot,
                ],

                'input_hash' => app(
                    AiSeoSnapshotHasher::class,
                )->hash([
                    'entity_snapshot' => $snapshot,
                ]),

                'provider' => $provider,

                'model_name' => 'test-model',

                'prompt_version' => 'ai-seo-metadata-v1',

                'completed_at' => now(),
            ]);
    }
}
