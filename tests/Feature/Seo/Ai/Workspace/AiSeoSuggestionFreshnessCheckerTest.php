<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionFreshnessChecker;
use App\Models\AiSeoRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoSuggestionFreshnessCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_fresh_then_stale_entity_snapshot(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Freshness Product',

                    'slug' => 'freshness-product',

                    'description' => 'Original factual content.',
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

        $checker =
            app(
                AiSeoSuggestionFreshnessChecker::class,
            );

        $fresh =
            $checker->check(
                $request,
            );

        self::assertFalse(
            $fresh->isStale,
        );

        self::assertSame(
            'fresh',
            $fresh->state,
        );

        $product->update([
            'description' => 'Changed factual content.',
        ]);

        $stale =
            $checker->check(
                $request,
            );

        self::assertTrue(
            $stale->isStale,
        );

        self::assertSame(
            'stale',
            $stale->state,
        );

        self::assertNotSame(
            $stale->sourceSnapshotHash,
            $stale->currentSnapshotHash,
        );
    }
}
