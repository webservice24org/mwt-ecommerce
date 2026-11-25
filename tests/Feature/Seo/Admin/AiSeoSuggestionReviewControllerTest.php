<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\Admin;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGovernedSeoSuggestionFixtures;
use Tests\TestCase;

final class AiSeoSuggestionReviewControllerTest extends TestCase
{
    use CreatesGovernedSeoSuggestionFixtures;
    use RefreshDatabase;

    public function test_approve_route_records_review_without_mutating_product_seo(): void
    {
        $this->withoutMiddleware();
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');

        [
            $product,
            $suggestion,
        ] =
            $this->suggestion();

        $response =
            $this->post(
                route(
                    'admin.seo.suggestions.approve',
                    [
                        'suggestionId' => $suggestion->id,
                    ],
                ),
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        self::assertDatabaseHas(
            'ai_seo_suggestion_reviews',
            [
                'ai_seo_suggestion_id' => $suggestion->id,

                'decision' => 'approved',
            ],
        );

        $product->refresh();

        self::assertNull(
            $product->meta_title,
        );
    }

    /**
     * @return array{0: Product, 1: AiSeoSuggestion}
     */
    private function suggestion(): array
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Controller Review Product',

                    'slug' => 'controller-review-product',

                    'description' => 'Stable factual content.',
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

        $suggestion =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => 'Suggested Controller Product Title',

                    'rationale' => 'Factual title suggestion.',

                    'position' => 0,
                ]);

        $this->governSeoFixture($request);

        return [
            $product,
            $suggestion,
        ];
    }
}
