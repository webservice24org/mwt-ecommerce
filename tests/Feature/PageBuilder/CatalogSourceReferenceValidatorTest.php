<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Services\SectionConfigurationValidator;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogSourceReferenceValidatorTest extends TestCase
{
    use RefreshDatabase;

    private SectionConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = app(
            SectionConfigurationValidator::class,
        );
    }

    public function test_featured_source_requires_no_catalog_reference(): void
    {
        $config = $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
        );

        $this->assertSame(
            'featured',
            $config['source']['type'],
        );
    }

    public function test_existing_category_reference_is_valid(): void
    {
        $category = Category::factory()->create();

        $config = $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Category Products',
                'limit' => 8,
                'source' => [
                    'type' => 'category',
                    'category_id' => $category->id,
                ],
            ],
        );

        $this->assertSame(
            $category->id,
            $config['source']['category_id'],
        );
    }

    public function test_missing_category_reference_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Category Products',
                'limit' => 8,
                'source' => [
                    'type' => 'category',
                    'category_id' => 999999,
                ],
            ],
        );
    }

    public function test_existing_manual_product_references_are_valid(): void
    {
        $products = Product::factory()
            ->count(3)
            ->create();

        $productIds = $products
            ->pluck('id')
            ->map(
                static fn (mixed $id): int => (int) $id,
            )
            ->all();

        $config = $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Our Picks',
                'limit' => 8,
                'source' => [
                    'type' => 'manual',
                    'product_ids' => $productIds,
                ],
            ],
        );

        $this->assertSame(
            $productIds,
            $config['source']['product_ids'],
        );
    }

    public function test_missing_manual_product_reference_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Our Picks',
                'limit' => 8,
                'source' => [
                    'type' => 'manual',
                    'product_ids' => [
                        $product->id,
                        999999,
                    ],
                ],
            ],
        );
    }
}
