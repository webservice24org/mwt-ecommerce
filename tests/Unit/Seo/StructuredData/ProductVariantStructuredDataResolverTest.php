<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Catalog\Data\Storefront\StorefrontAttributeValueData;
use App\Domain\Catalog\Data\Storefront\StorefrontBrandData;
use App\Domain\Catalog\Data\Storefront\StorefrontPricingData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Data\Storefront\StorefrontVariantData;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Services\ProductVariantStructuredDataResolver;
use Tests\TestCase;

final class ProductVariantStructuredDataResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',

            'app.url' => 'https://example.com',
        ]);
    }

    public function test_variable_product_resolves_product_group_with_variant_offers(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        sku: 'CABLE-GROUP',

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'CAB-BLK',

                                price: 85000,

                                stockQuantity: 5,

                                attributeValues: [
                                    $this->attributeValue(
                                        id: 1,

                                        name: 'Black',

                                        slug: 'black',

                                        attributeId: 10,

                                        attributeName: 'Color',

                                        attributeSlug: 'color',
                                    ),
                                ],
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        $group =
            $this->groupNode(
                $document,
            );

        self::assertSame(
            'ProductGroup',
            $group['@type'],
        );

        self::assertSame(
            'CABLE-GROUP',
            $group['productGroupID'],
        );

        self::assertSame(
            [
                'https://schema.org/color',
            ],
            $group['variesBy'],
        );

        self::assertCount(
            1,
            $group['hasVariant'],
        );

        $variant =
            $group['hasVariant'][0];

        self::assertSame(
            'Product',
            $variant['@type'],
        );

        self::assertSame(
            'USB Cable - Black',
            $variant['name'],
        );

        self::assertSame(
            'CAB-BLK',
            $variant['sku'],
        );

        self::assertSame(
            'Black',
            $variant['color'],
        );

        self::assertSame(
            'https://example.com/products/cable?variant=41',
            $variant['url'],
        );

        self::assertSame(
            '850.00',
            $variant['offers']['price'],
        );

        self::assertSame(
            'BDT',
            $variant['offers']['priceCurrency'],
        );

        self::assertSame(
            'https://schema.org/InStock',
            $variant['offers']['availability'],
        );
    }

    public function test_parent_sku_is_used_as_product_group_id_and_id_falls_back_to_product_id(): void
    {
        $withSku =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        id: 15,

                        sku: 'GROUP-SKU',

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $withSku,
        );

        self::assertSame(
            'GROUP-SKU',
            $this
                ->groupNode(
                    $withSku,
                )['productGroupID'],
        );

        $withoutSku =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        id: 15,

                        sku: null,

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $withoutSku,
        );

        self::assertSame(
            'product-15',
            $this
                ->groupNode(
                    $withoutSku,
                )['productGroupID'],
        );
    }

    public function test_supported_variant_properties_are_emitted_and_unsupported_properties_are_not_invented(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',

                                attributeValues: [
                                    $this->attributeValue(
                                        id: 1,

                                        name: 'Black',

                                        slug: 'black',

                                        attributeId: 10,

                                        attributeName: 'Color',

                                        attributeSlug: 'color',
                                    ),

                                    $this->attributeValue(
                                        id: 2,

                                        name: '2 Meter',

                                        slug: '2-meter',

                                        attributeId: 11,

                                        attributeName: 'Size',

                                        attributeSlug: 'size',
                                    ),

                                    $this->attributeValue(
                                        id: 3,

                                        name: '128 GB',

                                        slug: '128-gb',

                                        attributeId: 12,

                                        attributeName: 'Storage',

                                        attributeSlug: 'storage',
                                    ),
                                ],
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        $group =
            $this->groupNode(
                $document,
            );

        self::assertSame(
            [
                'https://schema.org/color',
                'https://schema.org/size',
            ],
            $group['variesBy'],
        );

        $variant =
            $group['hasVariant'][0];

        self::assertSame(
            'Black',
            $variant['color'],
        );

        self::assertSame(
            '2 Meter',
            $variant['size'],
        );

        self::assertArrayNotHasKey(
            'storage',
            $variant,
        );
    }

    public function test_variant_name_falls_back_to_explicit_name_then_sku(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',

                                name: 'Premium Edition',

                                attributeValues: [],
                            ),

                            $this->variant(
                                id: 42,

                                sku: 'VAR-42',

                                name: null,

                                attributeValues: [],
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        $variants =
            $this
                ->groupNode(
                    $document,
                )['hasVariant'];

        self::assertSame(
            'USB Cable - Premium Edition',
            $variants[0]['name'],
        );

        self::assertSame(
            'USB Cable - VAR-42',
            $variants[1]['name'],
        );
    }

    public function test_null_zero_and_negative_priced_variants_are_skipped(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        variants: [
                            $this->variant(
                                id: 40,

                                sku: 'NO-PRICE',

                                price: null,
                            ),

                            $this->variant(
                                id: 41,

                                sku: 'ZERO',

                                price: 0,
                            ),

                            $this->variant(
                                id: 42,

                                sku: 'NEGATIVE',

                                price: -1,
                            ),

                            $this->variant(
                                id: 43,

                                sku: 'VALID',

                                price: 85000,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        $variants =
            $this
                ->groupNode(
                    $document,
                )['hasVariant'];

        self::assertCount(
            1,
            $variants,
        );

        self::assertSame(
            'VALID',
            $variants[0]['sku'],
        );
    }

    public function test_all_unpurchasable_variants_return_null(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'NO-PRICE',

                                price: null,
                            ),

                            $this->variant(
                                id: 42,

                                sku: 'ZERO',

                                price: 0,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNull(
            $document,
        );
    }

    public function test_tracked_variant_availability_uses_variant_stock_quantity(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        trackInventory: true,

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'IN-STOCK',

                                stockQuantity: 5,
                            ),

                            $this->variant(
                                id: 42,

                                sku: 'OUT-OF-STOCK',

                                stockQuantity: 0,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        $variants =
            $this
                ->groupNode(
                    $document,
                )['hasVariant'];

        self::assertSame(
            'https://schema.org/InStock',
            $variants[0]['offers']['availability'],
        );

        self::assertSame(
            'https://schema.org/OutOfStock',
            $variants[1]['offers']['availability'],
        );
    }

    public function test_untracked_variable_product_marks_purchasable_variants_in_stock(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        trackInventory: false,

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',

                                stockQuantity: 0,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            'https://schema.org/InStock',
            $this
                ->groupNode(
                    $document,
                )['hasVariant'][0]['offers']['availability'],
        );
    }

    public function test_structured_data_disabled_returns_null(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(
                        enableStructuredData: false,
                    ),

                    product: $this->product(
                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNull(
            $document,
        );
    }

    public function test_simple_product_returns_null_from_variant_resolver(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        type: ProductType::Simple,

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNull(
            $document,
        );
    }

    public function test_unsafe_canonical_urls_are_rejected(): void
    {
        foreach (
            [
                'javascript:alert(1)',
                'data:text/html,test',
            ] as $canonicalUrl
        ) {
            self::assertNull(
                $this
                    ->resolver()
                    ->resolve(
                        settings: $this->settings(),

                        product: $this->product(
                            variants: [
                                $this->variant(
                                    id: 41,

                                    sku: 'VAR-41',
                                ),
                            ],
                        ),

                        canonicalUrl: $canonicalUrl,
                    ),
            );
        }
    }

    public function test_html_description_is_normalized_to_plain_text(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        shortDescription: '<p>Fast <strong>braided</strong> cable &amp; charger.</p>',

                        variants: [
                            $this->variant(
                                id: 41,

                                sku: 'VAR-41',
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/cable',
                );

        self::assertNotNull(
            $document,
        );

        self::assertSame(
            'Fast braided cable & charger.',
            $this
                ->groupNode(
                    $document,
                )['description'],
        );
    }

    private function resolver(): ProductVariantStructuredDataResolver
    {
        return app(
            ProductVariantStructuredDataResolver::class,
        );
    }

    /**
     * @param  list<StorefrontVariantData>  $variants
     */
    private function product(
        int $id = 10,
        ProductType $type = ProductType::Variable,
        string $name = 'USB Cable',
        string $slug = 'cable',
        ?string $sku = null,
        ?string $shortDescription = 'Fast USB cable.',
        ?string $description = null,
        bool $trackInventory = true,
        int $stockQuantity = 0,
        ?StorefrontBrandData $brand = null,
        array $variants = [],
    ): StorefrontProductDetailData {
        return new StorefrontProductDetailData(
            id: $id,

            name: $name,

            slug: $slug,

            type: $type,

            sku: $sku,

            shortDescription: $shortDescription,

            description: $description,

            pricing: new StorefrontPricingData(
                price: null,

                compareAtPrice: null,

                onSale: false,
            ),

            trackInventory: $trackInventory,

            stockQuantity: $stockQuantity,

            brand: $brand,

            categories: [],

            images: [],

            variants: $variants,

            metaTitle: null,

            metaDescription: null,

            video: null,
        );
    }

    /**
     * @param  list<StorefrontAttributeValueData>  $attributeValues
     */
    private function variant(
        int $id,
        string $sku,
        ?string $name = null,
        ?int $price = 85000,
        int $stockQuantity = 5,
        array $attributeValues = [],
    ): StorefrontVariantData {
        return new StorefrontVariantData(
            id: $id,

            sku: $sku,

            name: $name,

            isDefault: $id === 41,

            stockQuantity: $stockQuantity,

            pricing: new StorefrontPricingData(
                price: $price,

                compareAtPrice: null,

                onSale: false,
            ),

            attributeValues: $attributeValues,
        );
    }

    private function attributeValue(
        int $id,
        string $name,
        string $slug,
        int $attributeId,
        string $attributeName,
        string $attributeSlug,
    ): StorefrontAttributeValueData {
        return new StorefrontAttributeValueData(
            id: $id,

            name: $name,

            slug: $slug,

            attributeId: $attributeId,

            attributeName: $attributeName,

            attributeSlug: $attributeSlug,
        );
    }

    private function settings(
        bool $enableStructuredData = true,
    ): SeoSettingsData {
        return new SeoSettingsData(
            siteName: 'MWT Store',

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: 'Default store description.',

            canonicalHost: 'https://example.com',

            defaultRobotsIndex: true,

            defaultRobotsFollow: true,

            defaultOgImage: null,

            twitterCard: 'summary_large_image',

            organizationName: 'MWT Store',

            organizationLogo: null,

            organizationSocialProfiles: [],

            googleSiteVerification: null,

            bingSiteVerification: null,

            enableStructuredData: $enableStructuredData,

            enableOpenGraph: true,

            enableTwitterCards: true,

            enableSitemap: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function groupNode(
        StructuredDataDocument $document,
    ): array {
        $graph =
            $document
                ->toArray()['@graph'];

        self::assertCount(
            1,
            $graph,
        );

        self::assertSame(
            'ProductGroup',
            $graph[0]['@type'] ?? null,
        );

        return $graph[0];
    }
}
