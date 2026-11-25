<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkItemExecutionService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiSeoBulkItemExecutionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_through_existing_h1_h3_pipeline_without_mutating_live_seo(): void
    {
        config([
            'ai-seo.enabled' => true,

            'ai-seo.default' => 'openai',

            'ai-seo.openai.api_key' => 'test-key',

            'ai-seo.openai.base_url' => 'https://api.openai.test/v1',

            'ai-seo.openai.model' => 'gpt-6-luna',
        ]);

        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response(
                [
                    'id' => 'resp_bulk_success',

                    'output' => [
                        [
                            'type' => 'message',

                            'content' => [
                                [
                                    'type' => 'output_text',

                                    'text' => json_encode(
                                        [
                                            'suggestions' => [
                                                [
                                                    'field' => 'meta_title',

                                                    'value' => 'Bulk Generated Product Title',

                                                    'rationale' => 'Clear factual metadata title.',
                                                ],
                                            ],
                                        ],
                                        JSON_THROW_ON_ERROR,
                                    ),
                                ],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $product =
            Product::factory()
                ->create([
                    'name' => 'Bulk Execution Product',

                    'slug' => 'bulk-execution-product',

                    'meta_title' => 'Original Product SEO Title',

                    'description' => 'Stable factual product description.',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $item =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->firstOrFail();

        app(
            AiSeoBulkItemExecutionService::class,
        )->execute(
            $item->id,
        );

        $item->refresh();

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Completed,
            $item
                ->execution_status,
        );

        self::assertSame(
            1,
            $item
                ->attempt_count,
        );

        self::assertNotNull(
            $item
                ->ai_seo_request_id,
        );

        self::assertDatabaseHas(
            'ai_seo_requests',
            [
                'id' => $item
                    ->ai_seo_request_id,

                'status' => 'completed',
            ],
        );

        self::assertDatabaseHas(
            'ai_seo_suggestions',
            [
                'ai_seo_request_id' => $item
                    ->ai_seo_request_id,

                'field' => 'meta_title',

                'value' => 'Bulk Generated Product Title',
            ],
        );

        $product->refresh();

        self::assertSame(
            'Original Product SEO Title',
            $product
                ->meta_title,
        );
    }

    public function test_snapshot_drift_skips_provider_and_marks_item_drifted(): void
    {
        Http::fake();

        $product =
            Product::factory()
                ->create([
                    'description' => 'Original planning content.',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $item =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->firstOrFail();

        $product->update([
            'description' => 'Changed after dry-run planning.',
        ]);

        app(
            AiSeoBulkItemExecutionService::class,
        )->execute(
            $item->id,
        );

        $item->refresh();

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Drifted,
            $item
                ->execution_status,
        );

        self::assertSame(
            'snapshot_drift',
            $item
                ->execution_error_code,
        );

        self::assertDatabaseCount(
            'ai_seo_requests',
            0,
        );

        Http::assertNothingSent();
    }

    public function test_duplicate_delivery_reconciles_linked_completed_request_without_second_provider_call(): void
    {
        Http::fake();

        $product =
            Product::factory()
                ->create();

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $item =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->firstOrFail();

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

        $item->update([
            'ai_seo_request_id' => $request->id,
        ]);

        $service =
            app(
                AiSeoBulkItemExecutionService::class,
            );

        $service->execute(
            $item->id,
        );

        $service->execute(
            $item->id,
        );

        $item->refresh();

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Completed,
            $item
                ->execution_status,
        );

        self::assertSame(
            1,
            $item
                ->attempt_count,
        );

        self::assertSame(
            $request->id,
            $item
                ->ai_seo_request_id,
        );

        self::assertDatabaseCount(
            'ai_seo_requests',
            1,
        );

        Http::assertNothingSent();
    }
}
