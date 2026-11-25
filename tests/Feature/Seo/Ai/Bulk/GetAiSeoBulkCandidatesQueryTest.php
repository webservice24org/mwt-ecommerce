<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkCandidatesQuery;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetAiSeoBulkCandidatesQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_classifies_ready_fresh_and_stale_candidates(): void
    {
        $ready =
            Product::factory()
                ->create([
                    'name' => 'Ready Product',
                    'slug' => 'ready-product',
                ]);

        $fresh =
            Category::factory()
                ->create([
                    'name' => 'Fresh Category',
                    'slug' => 'fresh-category',
                ]);

        $stale =
            Brand::factory()
                ->create([
                    'name' => 'Stale Brand',
                    'slug' => 'stale-brand',
                ]);

        $this->completedRequest(
            AiSeoEntityType::Category,
            $fresh->id,
        );

        $this->completedRequest(
            AiSeoEntityType::Brand,
            $stale->id,
        );

        $stale->update([
            'description' => 'Changed after generation.',
        ]);

        $result =
            app(
                GetAiSeoBulkCandidatesQuery::class,
            )->handle([
                'product',
                'category',
                'brand',
            ]);

        $items =
            collect($result['items'])
                ->keyBy(
                    static fn (
                        array $item,
                    ): string => $item['entity_type']
                        .':'
                        .$item['entity_id'],
                );

        self::assertSame(
            'ready',
            $items['product:'.$ready->id]['state'],
        );

        self::assertSame(
            'fresh',
            $items['category:'.$fresh->id]['state'],
        );

        self::assertSame(
            'stale',
            $items['brand:'.$stale->id]['state'],
        );

        self::assertSame(
            2,
            $result['summary']['eligible'],
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
