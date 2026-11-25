<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Data\SeoEntitySourceData;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use Illuminate\Support\Facades\Storage;

final readonly class SeoEntitySourceFactory
{
    public function from(
        Product|Category|Brand|Page $entity,
    ): SeoEntitySourceData {
        if (
            $entity instanceof Product
        ) {
            return $this->fromProduct(
                $entity,
            );
        }

        if (
            $entity instanceof Category
        ) {
            return $this->fromCategory(
                $entity,
            );
        }

        if (
            $entity instanceof Brand
        ) {
            return $this->fromBrand(
                $entity,
            );
        }

        return $this->fromPage(
            $entity,
        );
    }

    private function fromProduct(
        Product $product,
    ): SeoEntitySourceData {
        if (
            ! $product->relationLoaded(
                'seoMeta',
            )
        ) {
            $product->load(
                'seoMeta',
            );
        }

        if (
            ! $product->relationLoaded(
                'featuredImage',
            )
        ) {
            $product->load(
                'featuredImage',
            );
        }

        return new SeoEntitySourceData(
            pageTitle: $product->name,

            metaTitle: $product->meta_title,

            metaDescription: $product->meta_description,

            fallbackDescription: $this->firstText([
                $product->short_description,
                $product->description,
            ]),

            fallbackImage: $this->publicImageUrl(
                $product
                    ->featuredImage
                    ?->path,
            ),

            overrides: $this->overrides(
                $product->seoMeta,
            ),
        );
    }

    private function fromCategory(
        Category $category,
    ): SeoEntitySourceData {
        if (
            ! $category->relationLoaded(
                'seoMeta',
            )
        ) {
            $category->load(
                'seoMeta',
            );
        }

        return new SeoEntitySourceData(
            pageTitle: $category->name,

            metaTitle: $category->meta_title,

            metaDescription: $category->meta_description,

            fallbackDescription: $this->plainText(
                $category->description,
            ),

            fallbackImage: $this->publicImageUrl(
                $category->image_path,
            ),

            overrides: $this->overrides(
                $category->seoMeta,
            ),
        );
    }

    private function fromBrand(
        Brand $brand,
    ): SeoEntitySourceData {
        if (
            ! $brand->relationLoaded(
                'seoMeta',
            )
        ) {
            $brand->load(
                'seoMeta',
            );
        }

        return new SeoEntitySourceData(
            pageTitle: $brand->name,

            metaTitle: $brand->meta_title,

            metaDescription: $brand->meta_description,

            fallbackDescription: $this->plainText(
                $brand->description,
            ),

            fallbackImage: $this->publicImageUrl(
                $brand->logo_path,
            ),

            overrides: $this->overrides(
                $brand->seoMeta,
            ),
        );
    }

    private function fromPage(
        Page $page,
    ): SeoEntitySourceData {
        if (
            ! $page->relationLoaded(
                'seoMeta',
            )
        ) {
            $page->load(
                'seoMeta',
            );
        }

        return new SeoEntitySourceData(
            pageTitle: $page->title,

            metaTitle: $page->meta_title,

            metaDescription: $page->meta_description,

            fallbackDescription: $this->plainText(
                $page->content,
            ),

            fallbackImage: $this->publicImageUrl(
                $page->featured_image,
            ),

            overrides: $this->overrides(
                $page->seoMeta,
            ),
        );
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function firstText(
        array $values,
    ): ?string {
        foreach (
            $values as $value
        ) {
            $value =
                $this->plainText(
                    $value,
                );

            if (
                $value !== null
            ) {
                return $value;
            }
        }

        return null;
    }

    private function plainText(
        ?string $value,
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        $value =
            html_entity_decode(
                $value,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            );

        $value =
            strip_tags(
                $value,
            );

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value,
            );

        if (
            ! is_string(
                $value,
            )
        ) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }

    private function publicImageUrl(
        ?string $value,
    ): ?string {
        if (
            $value === null
            || trim(
                $value,
            ) === ''
        ) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        if (
            str_starts_with(
                $value,
                'http://',
            )
            || str_starts_with(
                $value,
                'https://',
            )
        ) {
            return $value;
        }

        return url(
            Storage::url(
                $value,
            ),
        );
    }

    private function overrides(
        ?SeoMeta $seoMeta,
    ): EntitySeoData {
        if (
            $seoMeta === null
        ) {
            return EntitySeoData::empty();
        }

        return new EntitySeoData(
            title: null,

            description: null,

            canonicalUrl: $seoMeta->canonical_url,

            robotsIndex: $seoMeta->robots_index,

            robotsFollow: $seoMeta->robots_follow,

            ogTitle: $seoMeta->og_title,

            ogDescription: $seoMeta->og_description,

            ogImage: $seoMeta->og_image,

            twitterTitle: $seoMeta->twitter_title,

            twitterDescription: $seoMeta->twitter_description,

            twitterImage: $seoMeta->twitter_image,
        );
    }
}
