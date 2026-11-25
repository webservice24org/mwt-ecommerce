<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\StructuredData;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Reviews\Enums\ProductReviewStatus;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class EcommerceStructuredDataClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'commerce.currency' => 'BDT',
            'app.url' => 'https://example.com',
        ]);

        $this->saveSeoSettings();
    }

    public function test_simple_product_closure_preserves_global_identity_breadcrumb_offer_and_real_rating(): void
    {
        $product = $this->simpleProduct(
            slug: 'closure-charger',
            sku: 'CLOSURE-CHARGER',
            price: 485000,
            trackInventory: true,
            stockQuantity: 10,
        );

        $this->review(
            product: $product,
            rating: 5,
            status: ProductReviewStatus::Approved,
        );

        $this->review(
            product: $product,
            rating: 4,
            status: ProductReviewStatus::Approved,
        );

        $this->review(
            product: $product,
            rating: 1,
            status: ProductReviewStatus::Pending,
        );

        $canonical = 'https://example.com/products/closure-charger';

        $this
            ->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontStructuredData.@context',
                        'https://schema.org',
                    )
                    ->has('storefrontStructuredData.@graph', 2)
                    ->where(
                        'storefrontStructuredData.@graph.0.@type',
                        'Organization',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.0.@id',
                        'https://example.com/#organization',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.1.@type',
                        'WebSite',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.1.@id',
                        'https://example.com/#website',
                    )
                    ->where(
                        'storefrontStructuredData.@graph.1.publisher.@id',
                        'https://example.com/#organization',
                    )
                    ->where(
                        'storefrontStructuredData',
                        fn (mixed $document): bool => $this->assertCleanDocument($document),
                    )
                    ->where(
                        'structuredData.@context',
                        'https://schema.org',
                    )
                    ->has('structuredData.@graph', 2)
                    ->where(
                        'structuredData.@graph.0.@type',
                        'BreadcrumbList',
                    )
                    ->has(
                        'structuredData.@graph.0.itemListElement',
                        3,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.0.position',
                        1,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.1.position',
                        2,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.2.position',
                        3,
                    )
                    ->where(
                        'structuredData.@graph.0.itemListElement.2.name',
                        'Rated Charger',
                    )
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->where(
                        'structuredData.@graph.1.url',
                        $canonical,
                    )
                    ->where(
                        'structuredData.@graph.1.sku',
                        'CLOSURE-CHARGER',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.url',
                        $canonical,
                    )
                    ->where(
                        'structuredData.@graph.1.offers.price',
                        '4850.00',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.priceCurrency',
                        'BDT',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.availability',
                        'https://schema.org/InStock',
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.@type',
                        'AggregateRating',
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.ratingValue',
                        4.5,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.reviewCount',
                        2,
                    )
                    ->where(
                        'reviews.summary.average_rating',
                        4.5,
                    )
                    ->where(
                        'reviews.summary.review_count',
                        2,
                    )
                    ->where(
                        'structuredData',
                        fn (mixed $document): bool => $this->assertCleanDocument($document),
                    ),
            );
    }

    public function test_simple_out_of_stock_and_no_review_state_are_authoritative(): void
    {
        $product = $this->simpleProduct(
            slug: 'closure-out-of-stock',
            sku: 'CLOSURE-OUT',
            price: 125000,
            trackInventory: true,
            stockQuantity: 0,
        );

        $this->review(
            product: $product,
            rating: 5,
            status: ProductReviewStatus::Pending,
        );

        $this
            ->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.price',
                        '1250.00',
                    )
                    ->where(
                        'structuredData.@graph.1.offers.availability',
                        'https://schema.org/OutOfStock',
                    )
                    ->missing(
                        'structuredData.@graph.1.aggregateRating',
                    )
                    ->where(
                        'reviews.summary.review_count',
                        0,
                    ),
            );
    }

    public function test_zero_price_simple_product_never_emits_an_invalid_offer(): void
    {
        $product = $this->simpleProduct(
            slug: 'closure-zero-price',
            sku: 'CLOSURE-ZERO',
            price: 0,
            trackInventory: true,
            stockQuantity: 10,
        );

        $this
            ->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'structuredData.@graph.1.@type',
                        'Product',
                    )
                    ->missing(
                        'structuredData.@graph.1.offers',
                    ),
            );
    }

    public function test_variable_product_closure_keeps_one_group_rating_and_distinct_variant_urls(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Closure Cable',
                'slug' => 'closure-cable',
                'sku' => 'CLOSURE-CABLE-GROUP',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'track_inventory' => true,
                'stock_quantity' => 0,
            ]);

        $color = ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
                'is_active' => true,
            ]);

        $black = AttributeValue::factory()
            ->create([
                'attribute_id' => $color->id,
                'name' => 'Black',
                'slug' => 'black',
                'is_active' => true,
            ]);

        $white = AttributeValue::factory()
            ->create([
                'attribute_id' => $color->id,
                'name' => 'White',
                'slug' => 'white',
                'is_active' => true,
            ]);

        $blackVariant = ProductVariant::factory()
            ->create([
                'product_id' => $product->id,
                'sku' => 'CLOSURE-CABLE-BLACK',
                'name' => 'Black',
                'price' => 85000,
                'stock_quantity' => 5,
                'is_active' => true,
                'is_default' => true,
                'position' => 0,
            ]);

        $whiteVariant = ProductVariant::factory()
            ->create([
                'product_id' => $product->id,
                'sku' => 'CLOSURE-CABLE-WHITE',
                'name' => 'White',
                'price' => 90000,
                'stock_quantity' => 0,
                'is_active' => true,
                'is_default' => false,
                'position' => 1,
            ]);

        $blackVariant->attributeValues()->sync([$black->id]);
        $whiteVariant->attributeValues()->sync([$white->id]);

        $this->review(
            product: $product,
            rating: 5,
            status: ProductReviewStatus::Approved,
        );

        $this->review(
            product: $product,
            rating: 3,
            status: ProductReviewStatus::Approved,
        );

        $canonical = 'https://example.com/products/closure-cable';
        $blackUrl = $canonical.'?variant='.$blackVariant->id;
        $whiteUrl = $canonical.'?variant='.$whiteVariant->id;

        $this
            ->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('structuredData.@graph', 2)
                    ->where(
                        'structuredData.@graph.0.@type',
                        'BreadcrumbList',
                    )
                    ->where(
                        'structuredData.@graph.1.@type',
                        'ProductGroup',
                    )
                    ->where(
                        'structuredData.@graph.1.url',
                        $canonical,
                    )
                    ->where(
                        'structuredData.@graph.1.productGroupID',
                        'CLOSURE-CABLE-GROUP',
                    )
                    ->has(
                        'structuredData.@graph.1.hasVariant',
                        2,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.ratingValue',
                        4,
                    )
                    ->where(
                        'structuredData.@graph.1.aggregateRating.reviewCount',
                        2,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.sku',
                        'CLOSURE-CABLE-BLACK',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.url',
                        $blackUrl,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.offers.url',
                        $blackUrl,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.offers.price',
                        '850.00',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.offers.availability',
                        'https://schema.org/InStock',
                    )
                    ->missing(
                        'structuredData.@graph.1.hasVariant.0.aggregateRating',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.sku',
                        'CLOSURE-CABLE-WHITE',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.url',
                        $whiteUrl,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.offers.url',
                        $whiteUrl,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.offers.price',
                        '900.00',
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.1.offers.availability',
                        'https://schema.org/OutOfStock',
                    )
                    ->missing(
                        'structuredData.@graph.1.hasVariant.1.aggregateRating',
                    )
                    ->where(
                        'structuredData',
                        fn (mixed $document): bool => $this->assertCleanDocument($document),
                    ),
            );
    }

    public function test_variant_deep_link_keeps_base_group_canonical_and_preselects_only_the_requested_variant(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Deep Link Closure Cable',
                'slug' => 'deep-link-closure-cable',
                'sku' => 'DEEP-CLOSURE-GROUP',
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'track_inventory' => true,
                'stock_quantity' => 0,
            ]);

        $variant = ProductVariant::factory()
            ->create([
                'product_id' => $product->id,
                'sku' => 'DEEP-CLOSURE-VARIANT',
                'name' => 'Default',
                'price' => 99000,
                'stock_quantity' => 2,
                'is_active' => true,
                'is_default' => true,
                'position' => 0,
            ]);

        $canonical = 'https://example.com/products/deep-link-closure-cable';
        $variantUrl = $canonical.'?variant='.$variant->id;

        $this
            ->get('/products/'.$product->slug.'?variant='.$variant->id)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'selectedVariantId',
                        $variant->id,
                    )
                    ->where(
                        'structuredData.@graph.1.@type',
                        'ProductGroup',
                    )
                    ->where(
                        'structuredData.@graph.1.url',
                        $canonical,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.url',
                        $variantUrl,
                    )
                    ->where(
                        'structuredData.@graph.1.hasVariant.0.offers.url',
                        $variantUrl,
                    ),
            );
    }

    public function test_disabled_structured_data_nulls_both_global_and_page_documents(): void
    {
        $this->saveSeoSettings(enabled: false);

        $product = $this->simpleProduct(
            slug: 'closure-schema-disabled',
            sku: 'CLOSURE-DISABLED',
            price: 125000,
            trackInventory: true,
            stockQuantity: 5,
        );

        $this
            ->get('/products/'.$product->slug)
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where(
                        'storefrontStructuredData',
                        null,
                    )
                    ->where(
                        'structuredData',
                        null,
                    ),
            );
    }

    private function simpleProduct(
        string $slug,
        string $sku,
        int $price,
        bool $trackInventory,
        int $stockQuantity,
    ): Product {
        return Product::factory()
            ->create([
                'name' => 'Rated Charger',
                'slug' => $slug,
                'sku' => $sku,
                'price' => $price,
                'status' => ProductStatus::Published,
                'published_at' => now()->subMinute(),
                'track_inventory' => $trackInventory,
                'stock_quantity' => $stockQuantity,
            ]);
    }

    private function review(
        Product $product,
        int $rating,
        ProductReviewStatus $status,
    ): ProductReview {
        return ProductReview::factory()
            ->create([
                'product_id' => $product->id,
                'user_id' => User::factory()->create()->id,
                'rating' => $rating,
                'status' => $status,
            ]);
    }

    private function saveSeoSettings(bool $enabled = true): void
    {
        app(SeoSettingsService::class)->save(
            new SeoSettingsData(
                siteName: 'MWT Store',
                titleSeparator: '|',
                defaultTitleTemplate: '{page_title} {separator} {site_name}',
                defaultMetaDescription: null,
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
                enableStructuredData: $enabled,
                enableOpenGraph: true,
                enableTwitterCards: true,
                enableSitemap: true,
            ),
        );
    }

    private function assertCleanDocument(mixed $document): bool
    {
        if ($document instanceof Collection) {
            $document = $document->all();
        }

        self::assertIsArray($document);

        $this->assertNoNullBlankOrEmptyValues(
            value: $document,
            path: '$',
        );

        $definedIds = [];

        $this->collectDefinedIds(
            value: $document,
            path: '$',
            definedIds: $definedIds,
        );

        self::assertSame(
            array_values(array_unique($definedIds)),
            $definedIds,
            'Structured data contains duplicate defined @id values.',
        );

        return true;
    }

    private function assertNoNullBlankOrEmptyValues(
        mixed $value,
        string $path,
    ): void {
        if (is_array($value)) {
            self::assertNotSame(
                [],
                $value,
                "Structured data contains an empty array at {$path}.",
            );

            foreach ($value as $key => $nestedValue) {
                $this->assertNoNullBlankOrEmptyValues(
                    value: $nestedValue,
                    path: $path.'.'.(string) $key,
                );
            }

            return;
        }

        self::assertNotNull(
            $value,
            "Structured data contains null at {$path}.",
        );

        if (is_string($value)) {
            self::assertNotSame(
                '',
                trim($value),
                "Structured data contains a blank string at {$path}.",
            );
        }
    }

    /**
     * @param  list<string>  $definedIds
     */
    private function collectDefinedIds(
        mixed $value,
        string $path,
        array &$definedIds,
    ): void {
        if (! is_array($value)) {
            return;
        }

        $type = $value['@type'] ?? null;
        $id = $value['@id'] ?? null;

        if (
            (is_string($type) || is_array($type))
            && is_string($id)
            && trim($id) !== ''
        ) {
            self::assertTrue(
                filter_var($id, FILTER_VALIDATE_URL) !== false,
                "Structured data contains an invalid defined @id at {$path}.",
            );

            $definedIds[] = $id;
        }

        foreach ($value as $key => $nestedValue) {
            $this->collectDefinedIds(
                value: $nestedValue,
                path: $path.'.'.(string) $key,
                definedIds: $definedIds,
            );
        }
    }
}
