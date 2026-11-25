<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\GenerativeSearch;

use App\Domain\Seo\Data\ResolvedSeoData;
use App\Domain\Seo\GenerativeSearch\Enums\GenerativeSearchReadinessStatus;
use App\Domain\Seo\GenerativeSearch\Services\GenerativeSearchReadinessAnalyzer;
use DomainException;
use PHPUnit\Framework\TestCase;

final class GenerativeSearchReadinessAnalyzerTest extends TestCase
{
    public function test_well_supported_product_is_ready_without_fake_numeric_score(): void
    {
        $result =
            (new GenerativeSearchReadinessAnalyzer)
                ->analyze(
                    entitySnapshot: $this->snapshot(
                        type: 'product',

                        content: [
                            'short_description' => str_repeat(
                                'Useful product fact. ',
                                14,
                            ),
                        ],

                        context: [
                            'brand' => 'MicroWeb',

                            'categories' => [
                                'Accessories',
                            ],
                        ],
                    ),

                    resolvedSeo: $this->resolvedSeo(),

                    canonicalUrl: 'https://example.com/products/cable',

                    structuredDataTypes: [
                        'Product',
                        'BreadcrumbList',
                    ],

                    hasPrimaryImage: true,
                );

        self::assertSame(
            GenerativeSearchReadinessStatus::Ready,
            $result->status,
        );

        self::assertTrue(
            $result->eligibleForIndexing,
        );

        self::assertSame(
            [],
            $result->findings,
        );

        self::assertArrayNotHasKey(
            'score',
            $result->toArray(),
        );
    }

    public function test_noindex_is_a_real_blocker(): void
    {
        $result =
            (new GenerativeSearchReadinessAnalyzer)
                ->analyze(
                    entitySnapshot: $this->snapshot(
                        type: 'page',

                        content: [
                            'content' => str_repeat(
                                'Useful visible page content. ',
                                10,
                            ),
                        ],

                        context: [
                            'site' => 'MWT Store',
                        ],
                    ),

                    resolvedSeo: $this->resolvedSeo(
                        robotsIndex: false,
                    ),

                    canonicalUrl: 'https://example.com/about',

                    structuredDataTypes: [
                        'BreadcrumbList',
                    ],
                );

        self::assertSame(
            GenerativeSearchReadinessStatus::Blocked,
            $result->status,
        );

        self::assertFalse(
            $result->eligibleForIndexing,
        );

        self::assertSame(
            'indexing_blocked',
            $result
                ->findings[
                    0
                ]
                    ->id,
        );
    }

    public function test_missing_canonical_thin_content_and_context_are_needs_work_not_blocked(): void
    {
        $result =
            (new GenerativeSearchReadinessAnalyzer)
                ->analyze(
                    entitySnapshot: $this->snapshot(
                        type: 'brand',

                        content: [
                            'description' => 'Short brand.',
                        ],

                        context: [],
                    ),

                    resolvedSeo: $this->resolvedSeo(),

                    canonicalUrl: null,

                    structuredDataTypes: [],

                    hasPrimaryImage: false,
                );

        self::assertSame(
            GenerativeSearchReadinessStatus::NeedsWork,
            $result->status,
        );

        $ids =
            array_map(
                static fn ($finding): string => $finding->id,
                $result->findings,
            );

        self::assertContains(
            'canonical_missing',
            $ids,
        );

        self::assertContains(
            'visible_content_thin',
            $ids,
        );

        self::assertContains(
            'entity_context_missing',
            $ids,
        );

        self::assertContains(
            'primary_image_missing',
            $ids,
        );
    }

    public function test_brief_but_usable_content_is_informational_and_can_still_be_ready(): void
    {
        $result =
            (new GenerativeSearchReadinessAnalyzer)
                ->analyze(
                    entitySnapshot: $this->snapshot(
                        type: 'category',

                        content: [
                            'description' => str_repeat(
                                'Category fact. ',
                                8,
                            ),
                        ],

                        context: [
                            'parent' => 'Store',
                        ],
                    ),

                    resolvedSeo: $this->resolvedSeo(),

                    canonicalUrl: 'https://example.com/categories/accessories',

                    structuredDataTypes: [
                        'BreadcrumbList',
                    ],
                );

        self::assertSame(
            GenerativeSearchReadinessStatus::Ready,
            $result->status,
        );

        self::assertSame(
            'visible_content_brief',
            $result
                ->findings[
                    0
                ]
                    ->id,
        );

        self::assertSame(
            'info',
            $result
                ->findings[
                    0
                ]
                    ->severity
                    ->value,
        );
    }

    public function test_structured_data_types_are_normalized_deterministically(): void
    {
        $analyzer =
            new GenerativeSearchReadinessAnalyzer;

        $snapshot =
            $this->snapshot(
                type: 'product',

                content: [
                    'description' => str_repeat(
                        'Deterministic content. ',
                        12,
                    ),
                ],

                context: [
                    'brand' => 'MWT',
                ],
            );

        $first =
            $analyzer->analyze(
                entitySnapshot: $snapshot,

                resolvedSeo: $this->resolvedSeo(),

                canonicalUrl: 'https://example.com/products/item',

                structuredDataTypes: [
                    'Product',
                    'BreadcrumbList',
                    'Product',
                ],

                hasPrimaryImage: true,
            );

        $second =
            $analyzer->analyze(
                entitySnapshot: $snapshot,

                resolvedSeo: $this->resolvedSeo(),

                canonicalUrl: 'https://example.com/products/item',

                structuredDataTypes: [
                    'BreadcrumbList',
                    'Product',
                ],

                hasPrimaryImage: true,
            );

        self::assertSame(
            $first->toArray(),
            $second->toArray(),
        );

        self::assertSame(
            [
                'BreadcrumbList',
                'Product',
            ],
            $first
                ->signals[
                    'structured_data_types'
                ],
        );
    }

    public function test_unsupported_entity_type_is_rejected(): void
    {
        $this->expectException(
            DomainException::class,
        );

        (new GenerativeSearchReadinessAnalyzer)
            ->analyze(
                entitySnapshot: $this->snapshot(
                    type: 'order',

                    content: [],

                    context: [],
                ),

                resolvedSeo: $this->resolvedSeo(),

                canonicalUrl: 'https://example.com/orders/1',
            );
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $context
     * @return array{
     *     schema_version: string,
     *     entity: array{
     *         type: string,
     *         id: int,
     *         name: string,
     *         slug: string
     *     },
     *     content: array<string, mixed>,
     *     context: array<string, mixed>,
     *     current_seo: array<string, string|null>
     * }
     */
    private function snapshot(
        string $type,
        array $content,
        array $context,
    ): array {
        return [
            'schema_version' => 'ai-seo-entity-snapshot-v1',

            'entity' => [
                'type' => $type,

                'id' => 10,

                'name' => 'Example Entity',

                'slug' => 'example-entity',
            ],

            'content' => $content,

            'context' => $context,

            'current_seo' => [
                'meta_title' => null,

                'meta_description' => null,

                'og_title' => null,

                'og_description' => null,

                'twitter_title' => null,

                'twitter_description' => null,
            ],
        ];
    }

    private function resolvedSeo(
        bool $robotsIndex = true,
        bool $robotsFollow = true,
    ): ResolvedSeoData {
        return new ResolvedSeoData(
            title: 'Example Entity | MWT Store',

            description: 'A clear factual description for the example entity.',

            canonicalOverride: null,

            robotsIndex: $robotsIndex,

            robotsFollow: $robotsFollow,

            ogTitle: 'Example Entity',

            ogDescription: 'Open Graph description.',

            ogImage: null,

            twitterTitle: 'Example Entity',

            twitterDescription: 'X description.',

            twitterImage: null,
        );
    }
}
