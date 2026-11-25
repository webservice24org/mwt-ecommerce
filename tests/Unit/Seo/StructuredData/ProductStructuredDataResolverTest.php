<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Catalog\Data\Storefront\StorefrontBrandData;
use App\Domain\Catalog\Data\Storefront\StorefrontImageData;
use App\Domain\Catalog\Data\Storefront\StorefrontPricingData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Services\ProductStructuredDataResolver;
use Tests\TestCase;

final class ProductStructuredDataResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',

            'app.url' => 'https://example.com',
        ]);
    }

    public function test_simple_product_with_valid_price_resolves_product_and_offer(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        price: 485000,

                        sku: 'CHG-001',

                        brand: new StorefrontBrandData(
                            id: 10,

                            name: 'WiWU',

                            slug: 'wiwu',
                        ),

                        images: [
                            new StorefrontImageData(
                                id: 20,

                                url: 'https://example.com/storage/products/charger.jpg',

                                alt: 'USB Charger',

                                width: 1200,

                                height: 1200,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertSame(
            'Product',
            $product['@type'],
        );

        self::assertSame(
            'https://example.com/products/usb-charger#product',
            $product['@id'],
        );

        self::assertSame(
            'https://example.com/products/usb-charger',
            $product['url'],
        );

        self::assertSame(
            'USB Charger',
            $product['name'],
        );

        self::assertSame(
            'CHG-001',
            $product['sku'],
        );

        self::assertSame(
            [
                'https://example.com/storage/products/charger.jpg',
            ],
            $product['image'],
        );

        /**
         * @var array<string, mixed> $brand
         */
        $brand =
            $product['brand'];

        self::assertSame(
            'Brand',
            $brand['@type'],
        );

        self::assertSame(
            'WiWU',
            $brand['name'],
        );

        /**
         * @var array<string, mixed> $offer
         */
        $offer =
            $product['offers'];

        self::assertSame(
            'Offer',
            $offer['@type'],
        );

        self::assertSame(
            'https://example.com/products/usb-charger#offer',
            $offer['@id'],
        );

        self::assertSame(
            'https://example.com/products/usb-charger',
            $offer['url'],
        );
    }

    public function test_integer_price_is_formatted_from_minor_units_without_float_math(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        price: 485000,
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        /**
         * @var array<string, mixed> $offer
         */
        $offer =
            $product['offers'];

        self::assertSame(
            '4850.00',
            $offer['price'],
        );
    }

    public function test_offer_uses_configured_bdt_currency(): void
    {
        config([
            'commerce.currency' => 'BDT',
        ]);

        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        price: 485000,
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        /**
         * @var array<string, mixed> $offer
         */
        $offer =
            $product['offers'];

        self::assertSame(
            'BDT',
            $offer['priceCurrency'],
        );
    }

    public function test_simple_product_without_price_resolves_product_without_offer(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        price: null,
                    ),

                    canonicalUrl: 'https://example.com/products/no-price-product',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertSame(
            'Product',
            $product['@type'],
        );

        self::assertArrayNotHasKey(
            'offers',
            $product,
        );
    }

    public function test_zero_price_resolves_product_without_offer(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        price: 0,
                    ),

                    canonicalUrl: 'https://example.com/products/free-product',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertArrayNotHasKey(
            'offers',
            $product,
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
                        price: 485000,
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNull(
            $document,
        );
    }

    public function test_variable_product_returns_null_during_f4(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        type: ProductType::Variable,

                        price: 485000,
                    ),

                    canonicalUrl: 'https://example.com/products/variable-product',
                );

        self::assertNull(
            $document,
        );
    }

    public function test_javascript_and_data_canonical_urls_are_rejected(): void
    {
        foreach (
            [
                'javascript:alert(1)',
                'data:text/html,<script>alert(1)</script>',
            ] as $canonicalUrl
        ) {
            $document =
                $this->resolver()
                    ->resolve(
                        settings: $this->settings(),

                        product: $this->product(
                            price: 485000,
                        ),

                        canonicalUrl: $canonicalUrl,
                    );

            self::assertNull(
                $document,
                sprintf(
                    'Unsafe canonical URL [%s] should be rejected.',
                    $canonicalUrl,
                ),
            );
        }
    }

    public function test_relative_product_image_is_resolved_against_canonical_host(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(
                        canonicalHost: 'https://shop.example.com',
                    ),

                    product: $this->product(
                        images: [
                            new StorefrontImageData(
                                id: 1,

                                url: '/storage/products/charger.jpg',

                                alt: 'USB Charger',

                                width: 1200,

                                height: 1200,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://shop.example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertSame(
            [
                'https://shop.example.com/storage/products/charger.jpg',
            ],
            $product['image'],
        );
    }

    public function test_invalid_product_images_are_omitted(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        images: [
                            new StorefrontImageData(
                                id: 1,

                                url: 'javascript:alert(1)',

                                alt: null,

                                width: null,

                                height: null,
                            ),

                            new StorefrontImageData(
                                id: 2,

                                url: 'data:image/png;base64,abcdef',

                                alt: null,

                                width: null,

                                height: null,
                            ),

                            new StorefrontImageData(
                                id: 3,

                                url: '//evil.example.com/image.jpg',

                                alt: null,

                                width: null,

                                height: null,
                            ),

                            new StorefrontImageData(
                                id: 4,

                                url: 'not-a-valid-url',

                                alt: null,

                                width: null,

                                height: null,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertArrayNotHasKey(
            'image',
            $product,
        );
    }

    public function test_duplicate_valid_images_are_removed(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        images: [
                            new StorefrontImageData(
                                id: 1,

                                url: 'https://example.com/storage/products/charger.jpg',

                                alt: null,

                                width: 1200,

                                height: 1200,
                            ),

                            new StorefrontImageData(
                                id: 2,

                                url: 'https://example.com/storage/products/charger.jpg',

                                alt: null,

                                width: 600,

                                height: 600,
                            ),
                        ],
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertSame(
            [
                'https://example.com/storage/products/charger.jpg',
            ],
            $product['image'],
        );
    }

    public function test_html_short_description_is_converted_to_plain_text(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        shortDescription: '<p>Fast <strong>USB</strong> charger &amp; cable.</p>',
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertSame(
            'Fast USB charger & cable.',
            $product['description'],
        );

        self::assertStringNotContainsString(
            '<',
            (string) $product['description'],
        );

        self::assertStringNotContainsString(
            '>',
            (string) $product['description'],
        );
    }

    public function test_full_description_is_used_when_short_description_is_missing(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        shortDescription: null,

                        description: '<div>Full <em>product</em> description.</div>',
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertSame(
            'Full product description.',
            $product['description'],
        );
    }

    public function test_blank_optional_fields_are_omitted(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        sku: '   ',

                        shortDescription: '   ',

                        description: '   ',

                        brand: null,

                        images: [],
                    ),

                    canonicalUrl: 'https://example.com/products/usb-charger',
                );

        self::assertNotNull(
            $document,
        );

        $product =
            $this->productNode(
                $document,
            );

        self::assertArrayNotHasKey(
            'sku',
            $product,
        );

        self::assertArrayNotHasKey(
            'description',
            $product,
        );

        self::assertArrayNotHasKey(
            'brand',
            $product,
        );

        self::assertArrayNotHasKey(
            'image',
            $product,
        );
    }

    public function test_blank_product_name_returns_null(): void
    {
        $document =
            $this->resolver()
                ->resolve(
                    settings: $this->settings(),

                    product: $this->product(
                        name: '   ',
                    ),

                    canonicalUrl: 'https://example.com/products/invalid-product',
                );

        self::assertNull(
            $document,
        );
    }

    public function test_https_and_http_canonical_urls_are_allowed(): void
    {
        foreach (
            [
                'https://example.com/products/usb-charger',
                'http://example.com/products/usb-charger',
            ] as $canonicalUrl
        ) {
            $document =
                $this->resolver()
                    ->resolve(
                        settings: $this->settings(),

                        product: $this->product(),

                        canonicalUrl: $canonicalUrl,
                    );

            self::assertNotNull(
                $document,
            );
        }
    }

    private function resolver(): ProductStructuredDataResolver
    {
        return app(
            ProductStructuredDataResolver::class,
        );
    }

    /**
     * @param  list<StorefrontImageData>  $images
     */
    private function product(
        ProductType $type = ProductType::Simple,
        string $name = 'USB Charger',
        string $slug = 'usb-charger',
        ?string $sku = 'CHG-001',
        ?int $price = 485000,
        ?string $shortDescription = 'Fast USB charger.',
        ?string $description = null,
        bool $trackInventory = false,
        int $stockQuantity = 0,
        ?StorefrontBrandData $brand = null,
        array $images = [],
    ): StorefrontProductDetailData {
        return new StorefrontProductDetailData(
            id: 1,

            name: $name,

            slug: $slug,

            type: $type,

            sku: $sku,

            shortDescription: $shortDescription,

            description: $description,

            pricing: new StorefrontPricingData(
                price: $price,

                compareAtPrice: null,

                onSale: false,
            ),
            trackInventory: $trackInventory,

            stockQuantity: $stockQuantity,

            brand: $brand,

            categories: [],

            images: $images,

            variants: [],

            metaTitle: null,

            metaDescription: null,

            video: null,
        );
    }

    private function settings(
        bool $enableStructuredData = true,
        ?string $canonicalHost = 'https://example.com',
    ): SeoSettingsData {
        return new SeoSettingsData(
            siteName: 'MWT Store',

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: 'Default store description.',

            canonicalHost: $canonicalHost,

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
    private function productNode(
        StructuredDataDocument $document,
    ): array {
        $graph =
            $document
                ->toArray()['@graph'];

        self::assertCount(
            1,
            $graph,
        );

        $node =
            $graph[0];

        self::assertSame(
            'Product',
            $node['@type'] ?? null,
        );

        return $node;
    }
}
