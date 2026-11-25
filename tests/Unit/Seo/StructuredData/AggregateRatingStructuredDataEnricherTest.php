<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Reviews\Data\StorefrontProductReviewAggregateData;
use App\Domain\Seo\StructuredData\Services\AggregateRatingStructuredDataEnricher;
use PHPUnit\Framework\TestCase;

final class AggregateRatingStructuredDataEnricherTest extends TestCase
{
    public function test_it_attaches_aggregate_rating_to_simple_product(): void
    {
        $document =
            $this->enricher()
                ->enrich(
                    document: [
                        '@context' => 'https://schema.org',

                        '@graph' => [
                            [
                                '@type' => 'BreadcrumbList',
                            ],
                            [
                                '@type' => 'Product',

                                '@id' => 'https://example.com/products/charger#product',

                                'offers' => [
                                    '@type' => 'Offer',

                                    'price' => '4850.00',
                                ],
                            ],
                        ],
                    ],

                    aggregate: $this->aggregate(),
                );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            [
                '@type' => 'AggregateRating',

                'ratingValue' => 4.5,

                'reviewCount' => 2,

                'bestRating' => 5,

                'worstRating' => 1,
            ],
            $document['@graph'][1]['aggregateRating'],
        );

        self::assertSame(
            '4850.00',
            $document['@graph'][1]['offers']['price'],
        );
    }

    public function test_product_group_wins_and_variant_products_are_not_modified(): void
    {
        $document =
            $this->enricher()
                ->enrich(
                    document: [
                        '@context' => 'https://schema.org',

                        '@graph' => [
                            [
                                '@type' => 'BreadcrumbList',
                            ],
                            [
                                '@type' => 'ProductGroup',

                                '@id' => 'https://example.com/products/cable#product-group',

                                'hasVariant' => [
                                    [
                                        '@type' => 'Product',

                                        'sku' => 'CAB-BLK',
                                    ],
                                    [
                                        '@type' => 'Product',

                                        'sku' => 'CAB-WHT',
                                    ],
                                ],
                            ],
                        ],
                    ],

                    aggregate: $this->aggregate(),
                );

        self::assertNotNull(
            $document,
        );

        self::assertArrayHasKey(
            'aggregateRating',
            $document['@graph'][1],
        );

        self::assertArrayNotHasKey(
            'aggregateRating',
            $document['@graph'][1]['hasVariant'][0],
        );

        self::assertArrayNotHasKey(
            'aggregateRating',
            $document['@graph'][1]['hasVariant'][1],
        );
    }

    public function test_missing_or_invalid_aggregate_leaves_document_unchanged(): void
    {
        $document = [
            '@context' => 'https://schema.org',

            '@graph' => [
                [
                    '@type' => 'Product',

                    'name' => 'Charger',
                ],
            ],
        ];

        self::assertSame(
            $document,
            $this->enricher()
                ->enrich(
                    document: $document,

                    aggregate: null,
                ),
        );

        self::assertSame(
            $document,
            $this->enricher()
                ->enrich(
                    document: $document,

                    aggregate: new StorefrontProductReviewAggregateData(
                        ratingValue: 4.5,

                        reviewCount: 0,
                    ),
                ),
        );
    }

    public function test_non_product_graph_is_not_changed(): void
    {
        $document = [
            '@context' => 'https://schema.org',

            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                ],
                [
                    '@type' => 'Organization',
                ],
            ],
        ];

        self::assertSame(
            $document,
            $this->enricher()
                ->enrich(
                    document: $document,

                    aggregate: $this->aggregate(),
                ),
        );
    }

    private function enricher(): AggregateRatingStructuredDataEnricher
    {
        return new AggregateRatingStructuredDataEnricher;
    }

    private function aggregate(): StorefrontProductReviewAggregateData
    {
        return new StorefrontProductReviewAggregateData(
            ratingValue: 4.5,

            reviewCount: 2,
        );
    }
}
