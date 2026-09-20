<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services\Storefront;

use App\Domain\Catalog\Data\ResolvedProductPricing;
use App\Domain\Catalog\Data\Storefront\StorefrontAttributeValueData;
use App\Domain\Catalog\Data\Storefront\StorefrontBrandData;
use App\Domain\Catalog\Data\Storefront\StorefrontCategoryData;
use App\Domain\Catalog\Data\Storefront\StorefrontImageData;
use App\Domain\Catalog\Data\Storefront\StorefrontPricingData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Data\Storefront\StorefrontProductDetailData;
use App\Domain\Catalog\Data\Storefront\StorefrontVariantData;
use App\Domain\Catalog\Data\Storefront\StorefrontVideoData;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Domain\Catalog\Services\ProductPricingResolver;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use LogicException;

final readonly class StorefrontProductDataFactory
{
    public function __construct(
        private ProductPricingResolver $pricingResolver,
        private StorefrontProductListingPricingResolver $listingPricingResolver,
    ) {}

    public function card(
        Product $product,
    ): StorefrontProductCardData {
        $pricing = $this
            ->listingPricingResolver
            ->resolve($product);

        $image = $product->primaryImage;

        return new StorefrontProductCardData(
            id: $product->id,
            name: $product->name,
            slug: $product->slug,
            type: $product->type,
            shortDescription: $product->short_description,
            isFeatured: $product->is_featured,
            pricing: $pricing,
            image: $image instanceof ProductImage
                ? $this->image($image)
                : null,
            brand: $product->brand instanceof Brand
                ? $this->brand($product->brand)
                : null,
        );
    }

    public function detail(
        Product $product,
    ): StorefrontProductDetailData {
        $pricing = $this->pricingResolver->resolve(
            $product,
        );

        return new StorefrontProductDetailData(
            id: $product->id,
            name: $product->name,
            slug: $product->slug,
            type: $product->type,
            sku: $product->sku,
            shortDescription: $product->short_description,
            description: $product->description,
            pricing: $this->pricing($pricing),
            brand: $product->brand instanceof Brand
                ? $this->brand($product->brand)
                : null,

            categories: $product->categories
                ->map(
                    fn (Category $category): StorefrontCategoryData => $this
                        ->category($category),
                )
                ->values()
                ->all(),

            images: $product->images
                ->map(
                    fn (ProductImage $image): StorefrontImageData => $this
                        ->image($image),
                )
                ->values()
                ->all(),

            variants: $product->variants
                ->map(
                    fn (ProductVariant $variant): StorefrontVariantData => $this
                        ->variant(
                            product: $product,
                            variant: $variant,
                        ),
                )
                ->values()
                ->all(),

            metaTitle: $product->meta_title,
            metaDescription: $product->meta_description,
            video: $this->video($product),
        );
    }

    private function pricing(
        ResolvedProductPricing $pricing,
    ): StorefrontPricingData {
        return new StorefrontPricingData(
            price: $pricing->price,
            compareAtPrice: $pricing->compareAtPrice,
            onSale: $pricing->isOnSale(),
        );
    }

    private function image(
        ProductImage $image,
    ): StorefrontImageData {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return new StorefrontImageData(
            id: $image->id,
            url: $disk->url($image->path),
            alt: $image->alt_text,
            width: $image->width,
            height: $image->height,
        );
    }

    private function brand(
        Brand $brand,
    ): StorefrontBrandData {
        return new StorefrontBrandData(
            id: $brand->id,
            name: $brand->name,
            slug: $brand->slug,
        );
    }

    private function category(
        Category $category,
    ): StorefrontCategoryData {
        return new StorefrontCategoryData(
            id: $category->id,
            name: $category->name,
            slug: $category->slug,
        );
    }

    private function variant(
        Product $product,
        ProductVariant $variant,
    ): StorefrontVariantData {
        $pricing = $this->pricingResolver->resolve(
            product: $product,
            variant: $variant,
        );

        return new StorefrontVariantData(
            id: $variant->id,
            sku: $variant->sku,
            name: $variant->name,
            isDefault: $variant->is_default,
            pricing: $this->pricing($pricing),

            attributeValues: $variant
                ->attributeValues
                ->map(
                    fn (
                        AttributeValue $value,
                    ): StorefrontAttributeValueData => $this
                        ->attributeValue($value),
                )
                ->values()
                ->all(),
        );
    }

    private function attributeValue(
        AttributeValue $value,
    ): StorefrontAttributeValueData {
        $attribute = $value->attribute;

        if ($attribute === null) {
            throw new LogicException(
                'A storefront attribute value must have an attribute.',
            );
        }

        return new StorefrontAttributeValueData(
            id: $value->id,
            name: $value->name,
            slug: $value->slug,
            attributeId: $attribute->id,
            attributeName: $attribute->name,
            attributeSlug: $attribute->slug,
        );
    }

    private function video(Product $product): ?StorefrontVideoData
    {
        $video = $product->video;

        if ($video === null) {
            return null;
        }

        if ($video->type === ProductVideoType::Upload) {
            if ($video->path === null) {
                return null;
            }

            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            return new StorefrontVideoData(
                type: $video->type->value,
                url: $disk->url($video->path),
            );
        }

        if ($video->url === null) {
            return null;
        }

        return new StorefrontVideoData(
            type: $video->type->value,
            url: $video->url,
        );
    }
}
