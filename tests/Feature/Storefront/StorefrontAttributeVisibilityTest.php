<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Queries\Storefront\StorefrontFilterOptionsQuery;
use App\Domain\Catalog\Queries\Storefront\StorefrontProductDetailQuery;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontAttributeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.storefront_store',
            'array',
        );
    }

    public function test_active_attribute_and_active_value_are_public_in_product_detail(): void
    {
        [$product, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $this->assertCount(
            1,
            $detail->variants[0]->attributeValues,
        );

        $publicValue = $detail
            ->variants[0]
            ->attributeValues[0];

        $this->assertSame(
            $value->id,
            $publicValue->id,
        );

        $this->assertSame(
            'black',
            $publicValue->slug,
        );

        $this->assertSame(
            $attribute->id,
            $publicValue->attributeId,
        );

        $this->assertSame(
            'color',
            $publicValue->attributeSlug,
        );
    }

    public function test_inactive_value_is_hidden_from_public_product_detail(): void
    {
        [$product, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Private Red',
            slug: 'private-red',
            active: false,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $this->assertSame(
            [],
            $detail->variants[0]->attributeValues,
        );

        $encoded = json_encode(
            $detail->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'private-red',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'Private Red',
            $encoded,
        );
    }

    public function test_active_value_belonging_to_inactive_attribute_is_hidden_from_public_product_detail(): void
    {
        [$product, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Private Material',
            slug: 'private-material',
            active: false,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Secret Cotton',
            slug: 'secret-cotton',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $this->assertSame(
            [],
            $detail->variants[0]->attributeValues,
        );

        $encoded = json_encode(
            $detail->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'private-material',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'Private Material',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'secret-cotton',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'Secret Cotton',
            $encoded,
        );
    }

    public function test_variant_remains_public_when_its_only_attribute_value_is_hidden(): void
    {
        [$product, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Hidden Size',
            slug: 'hidden-size',
            active: false,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Hidden Large',
            slug: 'hidden-large',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $this->assertCount(
            1,
            $detail->variants,
        );

        $this->assertSame(
            $variant->id,
            $detail->variants[0]->id,
        );

        $this->assertSame(
            [],
            $detail->variants[0]->attributeValues,
        );
    }

    public function test_mixed_public_and_private_attribute_values_expose_only_public_values(): void
    {
        [$product, $variant] = $this->createPublicVariableProduct();

        $publicAttribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $publicValue = $this->createValue(
            attribute: $publicAttribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $inactiveValue = $this->createValue(
            attribute: $publicAttribute,
            name: 'Internal Red',
            slug: 'internal-red',
            active: false,
        );

        $inactiveAttribute = $this->createAttribute(
            name: 'Internal Material',
            slug: 'internal-material',
            active: false,
        );

        $inactiveAttributeValue = $this->createValue(
            attribute: $inactiveAttribute,
            name: 'Internal Cotton',
            slug: 'internal-cotton',
            active: true,
        );

        $variant->attributeValues()->attach([
            $publicValue->id,
            $inactiveValue->id,
            $inactiveAttributeValue->id,
        ]);

        $detail = $this->freshDetail(
            $product,
        );

        $this->assertNotNull($detail);

        $values = $detail
            ->variants[0]
            ->attributeValues;

        $this->assertCount(
            1,
            $values,
        );

        $this->assertSame(
            $publicValue->id,
            $values[0]->id,
        );

        $this->assertSame(
            'black',
            $values[0]->slug,
        );

        $encoded = json_encode(
            $detail->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'internal-red',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'internal-material',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'internal-cotton',
            $encoded,
        );
    }

    public function test_active_attribute_and_value_with_public_variant_appear_in_filter_options(): void
    {
        [, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $options = $this->freshFilterOptions();

        $publicAttribute = collect(
            $options->attributes,
        )->first(
            static fn ($item): bool => $item->id === $attribute->id,
        );

        $this->assertNotNull(
            $publicAttribute,
        );

        $this->assertTrue(
            collect($publicAttribute->values)
                ->contains(
                    static fn ($item): bool => $item->id === $value->id,
                ),
        );
    }

    public function test_inactive_attribute_is_hidden_from_filter_options_even_when_value_is_active(): void
    {
        [, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Internal Material',
            slug: 'internal-material',
            active: false,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Cotton',
            slug: 'cotton',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->attributes)
                ->contains(
                    static fn ($item): bool => $item->id === $attribute->id,
                ),
        );

        $encoded = json_encode(
            $options->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'internal-material',
            $encoded,
        );
    }

    public function test_inactive_value_is_hidden_from_filter_options(): void
    {
        [, $variant] = $this->createPublicVariableProduct();

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $activeValue = $this->createValue(
            attribute: $attribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $inactiveValue = $this->createValue(
            attribute: $attribute,
            name: 'Private Red',
            slug: 'private-red',
            active: false,
        );

        $variant->attributeValues()->attach([
            $activeValue->id,
            $inactiveValue->id,
        ]);

        $options = $this->freshFilterOptions();

        $publicAttribute = collect(
            $options->attributes,
        )->first(
            static fn ($item): bool => $item->id === $attribute->id,
        );

        $this->assertNotNull(
            $publicAttribute,
        );

        $this->assertTrue(
            collect($publicAttribute->values)
                ->contains(
                    static fn ($item): bool => $item->id === $activeValue->id,
                ),
        );

        $this->assertFalse(
            collect($publicAttribute->values)
                ->contains(
                    static fn ($item): bool => $item->id === $inactiveValue->id,
                ),
        );
    }

    public function test_attribute_used_only_by_inactive_variant_is_hidden_from_filter_options(): void
    {
        $product = $this->createPublishedVariableProduct();

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => false,
                'is_default' => true,
                'price' => 15000,
            ]);

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->attributes)
                ->contains(
                    static fn ($item): bool => $item->id === $attribute->id,
                ),
        );
    }

    public function test_attribute_used_only_by_draft_product_is_hidden_from_filter_options(): void
    {
        $product = $this->createVariableProduct(
            ProductStatus::Draft,
            null,
        );

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
            ]);

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->attributes)
                ->contains(
                    static fn ($item): bool => $item->id === $attribute->id,
                ),
        );
    }

    public function test_attribute_used_only_by_future_product_is_hidden_from_filter_options(): void
    {
        $product = $this->createVariableProduct(
            ProductStatus::Published,
            now()->addHour(),
        );

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
            ]);

        $attribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $value = $this->createValue(
            attribute: $attribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $variant->attributeValues()->attach(
            $value->id,
        );

        $options = $this->freshFilterOptions();

        $this->assertFalse(
            collect($options->attributes)
                ->contains(
                    static fn ($item): bool => $item->id === $attribute->id,
                ),
        );
    }

    public function test_cached_detail_preserves_attribute_visibility_rules(): void
    {
        [$product, $variant] = $this->createPublicVariableProduct();

        $publicAttribute = $this->createAttribute(
            name: 'Color',
            slug: 'color',
            active: true,
        );

        $publicValue = $this->createValue(
            attribute: $publicAttribute,
            name: 'Black',
            slug: 'black',
            active: true,
        );

        $privateAttribute = $this->createAttribute(
            name: 'Private Material',
            slug: 'private-material',
            active: false,
        );

        $privateValue = $this->createValue(
            attribute: $privateAttribute,
            name: 'Secret Cotton',
            slug: 'secret-cotton',
            active: true,
        );

        $variant->attributeValues()->attach([
            $publicValue->id,
            $privateValue->id,
        ]);

        $query = app(
            StorefrontProductDetailQuery::class,
        );

        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $first = $query->findBySlug(
            $product->slug,
        );

        $second = $query->findBySlug(
            $product->slug,
        );

        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->assertCount(
            1,
            $second->variants[0]->attributeValues,
        );

        $encoded = json_encode(
            $second->toArray(),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString(
            'black',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'private-material',
            $encoded,
        );

        $this->assertStringNotContainsString(
            'secret-cotton',
            $encoded,
        );
    }

    private function createPublicVariableProduct(): array
    {
        $product = $this->createPublishedVariableProduct();

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'is_active' => true,
                'is_default' => true,
                'price' => 15000,
            ]);

        return [
            $product,
            $variant,
        ];
    }

    private function createPublishedVariableProduct(): Product
    {
        return $this->createVariableProduct(
            ProductStatus::Published,
            now()->subMinute(),
        );
    }

    private function createVariableProduct(
        ProductStatus $status,
        mixed $publishedAt,
    ): Product {
        return Product::factory()
            ->variable()
            ->create([
                'type' => ProductType::Variable,
                'price' => 15000,
                'status' => $status,
                'published_at' => $publishedAt,
            ]);
    }

    private function createAttribute(
        string $name,
        string $slug,
        bool $active,
    ): ProductAttribute {
        return ProductAttribute::factory()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $active,
        ]);
    }

    private function createValue(
        ProductAttribute $attribute,
        string $name,
        string $slug,
        bool $active,
    ): AttributeValue {
        return AttributeValue::factory()
            ->for(
                $attribute,
                'attribute',
            )
            ->create([
                'name' => $name,
                'slug' => $slug,
                'is_active' => $active,
            ]);
    }

    private function freshDetail(
        Product $product,
    ): mixed {
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        return app(
            StorefrontProductDetailQuery::class,
        )->findBySlug(
            $product->slug,
        );
    }

    private function freshFilterOptions(): mixed
    {
        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        return app(
            StorefrontFilterOptionsQuery::class,
        )->get();
    }
}
