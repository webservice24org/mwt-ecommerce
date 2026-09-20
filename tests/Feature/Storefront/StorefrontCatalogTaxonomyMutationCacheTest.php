<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Actions\CreateAttributeAction;
use App\Domain\Catalog\Actions\CreateAttributeValueAction;
use App\Domain\Catalog\Actions\CreateBrandAction;
use App\Domain\Catalog\Actions\CreateCategoryAction;
use App\Domain\Catalog\Actions\DeleteAttributeAction;
use App\Domain\Catalog\Actions\DeleteAttributeValueAction;
use App\Domain\Catalog\Actions\DeleteBrandAction;
use App\Domain\Catalog\Actions\DeleteCategoryAction;
use App\Domain\Catalog\Actions\UpdateAttributeAction;
use App\Domain\Catalog\Actions\UpdateAttributeValueAction;
use App\Domain\Catalog\Actions\UpdateBrandAction;
use App\Domain\Catalog\Actions\UpdateCategoryAction;
use App\Domain\Catalog\Data\CreateAttributeData;
use App\Domain\Catalog\Data\CreateAttributeValueData;
use App\Domain\Catalog\Data\CreateBrandData;
use App\Domain\Catalog\Data\CreateCategoryData;
use App\Domain\Catalog\Data\UpdateAttributeData;
use App\Domain\Catalog\Data\UpdateAttributeValueData;
use App\Domain\Catalog\Data\UpdateBrandData;
use App\Domain\Catalog\Data\UpdateCategoryData;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class StorefrontCatalogTaxonomyMutationCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_creating_category_invalidates_storefront_cache(): void
    {
        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(CreateCategoryAction::class)->execute(
            new CreateCategoryData(
                name: 'Phones',
                slug: 'phones',
                parentId: null,
                description: 'Phones category',
                position: 1,
                isActive: true,
                metaTitle: 'Phones',
                metaDescription: 'Shop phones',
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_updating_category_invalidates_storefront_cache(): void
    {
        $category = Category::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(UpdateCategoryAction::class)->execute(
            $category,
            new UpdateCategoryData(
                name: 'Updated Category',
                slug: 'updated-category',
                parentId: null,
                description: 'Updated description',
                position: 2,
                isActive: true,
                metaTitle: 'Updated Category',
                metaDescription: 'Updated category description',
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_deleting_category_invalidates_storefront_cache(): void
    {
        $category = Category::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(DeleteCategoryAction::class)->execute(
            $category,
        );

        $this->assertVersionIncremented($before);
    }

    public function test_creating_brand_invalidates_storefront_cache(): void
    {
        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(CreateBrandAction::class)->execute(
            new CreateBrandData(
                name: 'Apple',
                slug: 'apple',
                description: 'Apple products',
                position: 1,
                isActive: true,
                metaTitle: 'Apple',
                metaDescription: 'Shop Apple products',
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_updating_brand_invalidates_storefront_cache(): void
    {
        $brand = Brand::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(UpdateBrandAction::class)->execute(
            $brand,
            new UpdateBrandData(
                name: 'Updated Brand',
                slug: 'updated-brand',
                description: 'Updated description',
                position: 2,
                isActive: true,
                metaTitle: 'Updated Brand',
                metaDescription: 'Updated brand description',
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_deleting_brand_invalidates_storefront_cache(): void
    {
        $brand = Brand::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(DeleteBrandAction::class)->execute(
            $brand,
        );

        $this->assertVersionIncremented($before);
    }

    public function test_creating_attribute_invalidates_storefront_cache(): void
    {
        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(CreateAttributeAction::class)->execute(
            new CreateAttributeData(
                name: 'Color',
                slug: 'color',
                position: 1,
                isActive: true,
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_updating_attribute_invalidates_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(UpdateAttributeAction::class)->execute(
            $attribute,
            new UpdateAttributeData(
                name: 'Updated Attribute',
                slug: 'updated-attribute',
                position: 2,
                isActive: true,
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_deleting_attribute_invalidates_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(DeleteAttributeAction::class)->execute(
            $attribute,
        );

        $this->assertVersionIncremented($before);
    }

    public function test_creating_attribute_value_invalidates_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(CreateAttributeValueAction::class)->execute(
            $attribute,
            new CreateAttributeValueData(
                name: 'Black',
                slug: 'black',
                position: 1,
                isActive: true,
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_updating_attribute_value_invalidates_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(UpdateAttributeValueAction::class)->execute(
            $value,
            new UpdateAttributeValueData(
                name: 'Updated Value',
                slug: 'updated-value',
                position: 2,
                isActive: true,
            ),
        );

        $this->assertVersionIncremented($before);
    }

    public function test_deleting_attribute_value_invalidates_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        $cache = $this->storefrontCache();
        $before = $cache->version();

        app(DeleteAttributeValueAction::class)->execute(
            $value,
        );

        $this->assertVersionIncremented($before);
    }

    private function storefrontCache(): StorefrontCatalogCache
    {
        return app(StorefrontCatalogCache::class);
    }

    private function assertVersionIncremented(
        int $before,
    ): void {
        $this->assertSame(
            $before + 1,
            $this->storefrontCache()->version(),
        );
    }

    public function test_rejected_attribute_deletion_does_not_invalidate_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        $variant = ProductVariant::factory()->create();

        $variant
            ->attributeValues()
            ->sync([$value->id]);

        $cache = $this->storefrontCache();
        $before = $cache->version();

        try {
            app(DeleteAttributeAction::class)->execute(
                $attribute,
            );

            $this->fail(
                'Expected attribute deletion to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'attribute',
                $exception->errors(),
            );
        }

        $this->assertSame(
            $before,
            $cache->version(),
        );

        $this->assertDatabaseHas(
            'attributes',
            [
                'id' => $attribute->id,
            ],
        );

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
                'attribute_id' => $attribute->id,
            ],
        );

        $this->assertDatabaseHas(
            'product_variant_values',
            [
                'product_variant_id' => $variant->id,
                'attribute_value_id' => $value->id,
            ],
        );
    }

    public function test_rejected_attribute_value_deletion_does_not_invalidate_storefront_cache(): void
    {
        $attribute = ProductAttribute::factory()->create();

        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        $variant = ProductVariant::factory()->create();

        $variant
            ->attributeValues()
            ->sync([$value->id]);

        $cache = $this->storefrontCache();
        $before = $cache->version();

        try {
            app(DeleteAttributeValueAction::class)->execute(
                $value,
            );

            $this->fail(
                'Expected attribute value deletion to be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'attribute_value',
                $exception->errors(),
            );
        }

        $this->assertSame(
            $before,
            $cache->version(),
        );

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
                'attribute_id' => $attribute->id,
            ],
        );

        $this->assertDatabaseHas(
            'product_variant_values',
            [
                'product_variant_id' => $variant->id,
                'attribute_value_id' => $value->id,
            ],
        );
    }
}
