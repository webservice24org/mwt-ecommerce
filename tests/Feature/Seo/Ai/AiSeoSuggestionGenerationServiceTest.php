<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai;

use App\Domain\Seo\Ai\Contracts\AiSeoProvider;
use App\Domain\Seo\Ai\Data\AiSeoProviderResultData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Exceptions\AiSeoSuggestionValidationException;
use App\Domain\Seo\Ai\Services\AiSeoDraftRequestService;
use App\Domain\Seo\Ai\Services\AiSeoSuggestionGenerationService;
use App\Models\AiSeoRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoSuggestionGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_persists_reviewable_suggestions_without_mutating_live_seo(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Existing Cable',

                    'slug' => 'existing-cable',

                    'meta_title' => 'Existing Meta Title',

                    'meta_description' => 'Existing meta description.',
                ]);

        $request =
            app(
                AiSeoDraftRequestService::class,
            )->create(
                AiSeoEntityType::Product,
                $product->id,
                55,
            );

        $completed =
            app(
                AiSeoSuggestionGenerationService::class,
            )->execute(
                $request,
                $this->validProvider(),
            );

        self::assertSame(
            AiSeoRequestStatus::Completed,
            $completed->status,
        );

        self::assertSame(
            'test-provider',
            $completed->provider,
        );

        self::assertSame(
            'test-model',
            $completed->model_name,
        );

        self::assertSame(
            'ai-seo-metadata-v1',
            $completed->prompt_version,
        );

        self::assertCount(
            2,
            $completed->suggestions,
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

    public function test_invalid_provider_output_marks_request_failed_and_persists_no_suggestions(): void
    {
        $product =
            Product::factory()
                ->create();

        $request =
            app(
                AiSeoDraftRequestService::class,
            )->create(
                AiSeoEntityType::Product,
                $product->id,
            );

        try {
            app(
                AiSeoSuggestionGenerationService::class,
            )->execute(
                $request,
                $this->unsafeProvider(),
            );

            self::fail(
                'Unsafe provider output was accepted.',
            );
        } catch (
            AiSeoSuggestionValidationException
        ) {
            self::assertTrue(
                true,
            );
        }

        $failed =
            AiSeoRequest::query()
                ->findOrFail(
                    $request->id,
                );

        self::assertSame(
            AiSeoRequestStatus::Failed,
            $failed->status,
        );

        self::assertSame(
            'invalid_provider_output',
            $failed->error_code,
        );

        self::assertSame(
            0,
            $failed
                ->suggestions()
                ->count(),
        );
    }

    private function validProvider(): AiSeoProvider
    {
        return new class implements AiSeoProvider
        {
            public function name(): string
            {
                return 'test-provider';
            }

            public function model(): string
            {
                return 'test-model';
            }

            public function generate(
                array $context,
            ): AiSeoProviderResultData {
                return new AiSeoProviderResultData(
                    payload: [
                        'suggestions' => [
                            [
                                'field' => 'meta_title',

                                'value' => 'Existing Cable | MWT Store',

                                'rationale' => 'Uses the known product name.',
                            ],

                            [
                                'field' => 'meta_description',

                                'value' => 'Explore the Existing Cable from MWT Store.',

                                'rationale' => null,
                            ],
                        ],
                    ],

                    rawResponse: [
                        'id' => 'test-response',
                    ],
                );
            }
        };
    }

    private function unsafeProvider(): AiSeoProvider
    {
        return new class implements AiSeoProvider
        {
            public function name(): string
            {
                return 'unsafe-test-provider';
            }

            public function model(): string
            {
                return 'unsafe-model';
            }

            public function generate(
                array $context,
            ): AiSeoProviderResultData {
                return new AiSeoProviderResultData(
                    payload: [
                        'suggestions' => [
                            [
                                'field' => 'canonical_url',

                                'value' => 'https://malicious.example/change-me',
                            ],
                        ],
                    ],

                    rawResponse: [
                        'id' => 'unsafe-response',
                    ],
                );
            }
        };
    }
}
