<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Seo\Ai\Data\AiSeoSuggestionData;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoRequestLifecycleManager;
use App\Models\AiSeoRequest;
use App\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class AiSeoRequestLifecycleManagerTest extends TestCase
{
    use RefreshDatabase;

    private AiSeoRequestLifecycleManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager =
            app(
                AiSeoRequestLifecycleManager::class,
            );
    }

    public function test_it_creates_an_immutable_pending_request_snapshot(): void
    {
        $request =
            $this->manager->create(
                entityType: AiSeoEntityType::Product,

                entityId: 77,

                action: AiSeoAction::GenerateMetadataSuggestions,

                inputSnapshot: [
                    'name' => 'USB Cable',

                    'current_seo' => [
                        'meta_title' => null,

                        'meta_description' => null,
                    ],
                ],

                requestedByAdminId: 12,
            );

        self::assertSame(
            AiSeoRequestStatus::Pending,
            $request->status,
        );

        self::assertSame(
            AiSeoEntityType::Product,
            $request->entity_type,
        );

        self::assertSame(
            77,
            $request->entity_id,
        );

        self::assertSame(
            12,
            $request->requested_by_admin_id,
        );

        self::assertSame(
            64,
            strlen(
                $request->input_hash,
            ),
        );

        self::assertSame(
            'USB Cable',
            $request->input_snapshot[
                'name'
            ],
        );

        self::assertNull(
            $request->provider,
        );
    }

    public function test_processing_and_completion_persist_provider_and_safe_suggestions(): void
    {
        $request =
            $this->pendingRequest();

        $processing =
            $this->manager->start(
                request: $request,

                provider: 'test-provider',

                modelName: 'test-model',

                promptVersion: 'seo-metadata-v1',
            );

        self::assertSame(
            AiSeoRequestStatus::Processing,
            $processing->status,
        );

        self::assertSame(
            'test-provider',
            $processing->provider,
        );

        self::assertNotNull(
            $processing->started_at,
        );

        $completed =
            $this->manager->complete(
                request: $processing,

                suggestions: [
                    new AiSeoSuggestionData(
                        field: AiSeoSuggestionField::MetaTitle,

                        value: 'Fast USB Cable | MWT Store',

                        rationale: 'Concise product-focused title.',
                    ),

                    new AiSeoSuggestionData(
                        field: AiSeoSuggestionField::MetaDescription,

                        value: 'Shop a fast USB cable for reliable everyday charging.',
                    ),
                ],

                rawResponse: [
                    'request_id' => 'provider-test-123',
                ],
            );

        self::assertSame(
            AiSeoRequestStatus::Completed,
            $completed->status,
        );

        self::assertNotNull(
            $completed->completed_at,
        );

        self::assertCount(
            2,
            $completed->suggestions,
        );

        self::assertSame(
            AiSeoSuggestionField::MetaTitle,
            $completed
                ->suggestions
                ->firstOrFail()
                ->field,
        );

        self::assertSame(
            'provider-test-123',
            $completed->raw_response[
                'request_id'
            ],
        );
    }

    public function test_completion_does_not_mutate_product_or_live_seo_fields(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Existing Product',

                    'slug' => 'existing-product',

                    'meta_title' => 'Existing Meta Title',

                    'meta_description' => 'Existing meta description.',

                    'status' => ProductStatus::Published,

                    'published_at' => now()
                        ->subMinute(),
                ]);

        $request =
            $this->manager->create(
                entityType: AiSeoEntityType::Product,

                entityId: $product->id,

                action: AiSeoAction::GenerateMetadataSuggestions,

                inputSnapshot: [
                    'name' => $product->name,
                ],
            );

        $request =
            $this->manager->start(
                $request,
                'test-provider',
                'test-model',
                'seo-metadata-v1',
            );

        $this->manager->complete(
            $request,
            [
                new AiSeoSuggestionData(
                    AiSeoSuggestionField::MetaTitle,
                    'AI Proposed Title',
                ),
            ],
        );

        $product->refresh();

        self::assertSame(
            'Existing Meta Title',
            $product->meta_title,
        );

        self::assertSame(
            'Existing meta description.',
            $product->meta_description,
        );

        self::assertNull(
            $product
                ->seoMeta()
                ->first(),
        );
    }

    public function test_completed_request_cannot_be_started_again(): void
    {
        $request =
            $this->pendingRequest();

        $request =
            $this->manager->start(
                $request,
                'test-provider',
                'test-model',
                'seo-metadata-v1',
            );

        $request =
            $this->manager->complete(
                $request,
                [
                    new AiSeoSuggestionData(
                        AiSeoSuggestionField::MetaTitle,
                        'Suggested title',
                    ),
                ],
            );

        $this->expectException(
            DomainException::class,
        );

        $this->manager->start(
            $request,
            'test-provider',
            'test-model',
            'seo-metadata-v1',
        );
    }

    public function test_duplicate_suggestion_fields_are_rejected(): void
    {
        $request =
            $this->pendingRequest();

        $request =
            $this->manager->start(
                $request,
                'test-provider',
                'test-model',
                'seo-metadata-v1',
            );

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->manager->complete(
            $request,
            [
                new AiSeoSuggestionData(
                    AiSeoSuggestionField::MetaTitle,
                    'First title',
                ),

                new AiSeoSuggestionData(
                    AiSeoSuggestionField::MetaTitle,
                    'Second title',
                ),
            ],
        );
    }

    public function test_request_can_fail_without_creating_suggestions(): void
    {
        $request =
            $this->pendingRequest();

        $failed =
            $this->manager->fail(
                request: $request,

                errorCode: 'provider_unavailable',

                errorMessage: 'Provider is temporarily unavailable.',
            );

        self::assertSame(
            AiSeoRequestStatus::Failed,
            $failed->status,
        );

        self::assertSame(
            'provider_unavailable',
            $failed->error_code,
        );

        self::assertNotNull(
            $failed->completed_at,
        );

        self::assertSame(
            0,
            $failed
                ->suggestions()
                ->count(),
        );
    }

    private function pendingRequest(): AiSeoRequest
    {
        return $this->manager->create(
            entityType: AiSeoEntityType::Product,

            entityId: 10,

            action: AiSeoAction::GenerateMetadataSuggestions,

            inputSnapshot: [
                'name' => 'USB Cable',
            ],
        );
    }
}
