<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class GetSectionDefinitionsQueryTest extends TestCase
{
    public function test_query_returns_only_registered_section_definitions(): void
    {
        $query = new GetSectionDefinitionsQuery(
            new SectionRegistry,
        );

        $definitions = $query->handle();

        $this->assertCount(
            9,
            $definitions,
        );

        /*
        |--------------------------------------------------------------------------
        | Hero
        |--------------------------------------------------------------------------
        */

        $hero = $definitions[0];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $hero,
        );

        $this->assertSame(
            SectionType::Hero->value,
            $hero->type,
        );

        $this->assertSame(
            'Hero',
            $hero->label,
        );

        $this->assertSame(
            'content_slider',
            $hero->defaultTemplate,
        );

        $this->assertCount(
            3,
            $hero->templates,
        );

        $this->assertSame(
            'content_slider',
            $hero->templates[0]['key'],
        );

        $this->assertSame(
            'Content Slider',
            $hero->templates[0]['label'],
        );

        $this->assertSame(
            'Hero',
            $hero->templates[0]['category'],
        );

        $this->assertSame(
            'image_slider',
            $hero->templates[1]['key'],
        );

        $this->assertSame(
            'Image Slider',
            $hero->templates[1]['label'],
        );

        $this->assertSame(
            'Hero',
            $hero->templates[1]['category'],
        );

        $this->assertSame(
            'static',
            $hero->templates[2]['key'],
        );

        $this->assertSame(
            'Static Hero',
            $hero->templates[2]['label'],
        );

        $this->assertSame(
            'Hero',
            $hero->templates[2]['category'],
        );

        $this->assertSame(
            'slide_left',
            $hero->defaultConfig['effect'],
        );

        $this->assertTrue(
            $hero->defaultConfig['autoplay'],
        );

        $this->assertSame(
            5000,
            $hero->defaultConfig['autoplay_delay'],
        );

        $this->assertTrue(
            $hero->defaultConfig['show_arrows'],
        );

        $this->assertTrue(
            $hero->defaultConfig['show_dots'],
        );

        $this->assertCount(
            1,
            $hero->defaultConfig['slides'],
        );

        /*
        |--------------------------------------------------------------------------
        | Hero Template-Specific Defaults
        |--------------------------------------------------------------------------
        */

        $this->assertCount(
            3,
            $hero->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'content_slider',
            $hero->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'image_slider',
            $hero->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'static',
            $hero->templateDefaultConfigs,
        );

        $contentSliderConfig =
            $hero->templateDefaultConfigs['content_slider'];

        $this->assertTrue(
            $contentSliderConfig['autoplay'],
        );

        $this->assertTrue(
            $contentSliderConfig['show_arrows'],
        );

        $this->assertTrue(
            $contentSliderConfig['show_dots'],
        );

        $this->assertSame(
            'slide_left',
            $contentSliderConfig['effect'],
        );

        $this->assertSame(
            $hero->defaultConfig,
            $contentSliderConfig,
        );

        $imageSliderConfig =
            $hero->templateDefaultConfigs['image_slider'];

        $this->assertTrue(
            $imageSliderConfig['autoplay'],
        );

        $this->assertTrue(
            $imageSliderConfig['show_arrows'],
        );

        $this->assertTrue(
            $imageSliderConfig['show_dots'],
        );

        $this->assertSame(
            'slide_left',
            $imageSliderConfig['effect'],
        );

        $this->assertArrayHasKey(
            'image',
            $imageSliderConfig['slides'][0],
        );

        $this->assertNull(
            $imageSliderConfig['slides'][0]['image'],
        );

        $staticConfig =
            $hero->templateDefaultConfigs['static'];

        $this->assertFalse(
            $staticConfig['autoplay'],
        );

        $this->assertFalse(
            $staticConfig['show_arrows'],
        );

        $this->assertFalse(
            $staticConfig['show_dots'],
        );

        $this->assertSame(
            'fade',
            $staticConfig['effect'],
        );

        $this->assertCount(
            1,
            $staticConfig['slides'],
        );

        /*
        |--------------------------------------------------------------------------
        | Featured Products
        |--------------------------------------------------------------------------
        */

        $featuredProducts = $definitions[1];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $featuredProducts,
        );

        $this->assertSame(
            SectionType::FeaturedProducts->value,
            $featuredProducts->type,
        );

        $this->assertSame(
            'Featured Products',
            $featuredProducts->label,
        );

        $this->assertSame(
            'grid',
            $featuredProducts->defaultTemplate,
        );

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $featuredProducts->defaultConfig,
        );

        $this->assertCount(
            1,
            $featuredProducts->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'grid',
            $featuredProducts->templateDefaultConfigs,
        );

        $this->assertSame(
            $featuredProducts->defaultConfig,
            $featuredProducts->templateDefaultConfigs['grid'],
        );

        $this->assertCount(
            1,
            $featuredProducts->templates,
        );

        $template = $featuredProducts->templates[0];

        $this->assertSame(
            'grid',
            $template['key'],
        );

        $this->assertSame(
            'Product Grid',
            $template['label'],
        );

        $this->assertSame(
            'Display a curated selection of featured products in a responsive grid.',
            $template['description'],
        );

        $this->assertSame(
            'Products',
            $template['category'],
        );

        /*
        |--------------------------------------------------------------------------
        | Product Categories
        |--------------------------------------------------------------------------
        */

        $productCategories = $definitions[2];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $productCategories,
        );

        $this->assertSame(
            SectionType::ProductCategories->value,
            $productCategories->type,
        );

        $this->assertSame(
            'Product Categories',
            $productCategories->label,
        );

        $this->assertSame(
            'grid',
            $productCategories->defaultTemplate,
        );

        $categoryGridConfig = [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
        ];

        $categoryCardsConfig = [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 3,
            'show_product_count' => true,
        ];

        $categoryCarouselConfig = [
            'title' => 'Shop by Category',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'fade',
        ];

        $this->assertSame(
            $categoryGridConfig,
            $productCategories->defaultConfig,
        );

        $this->assertCount(
            3,
            $productCategories->templates,
        );

        $this->assertSame(
            [
                'grid',
                'cards',
                'carousel',
            ],
            array_column(
                $productCategories->templates,
                'key',
            ),
        );

        $this->assertSame(
            [
                'Categories',
                'Categories',
                'Categories',
            ],
            array_column(
                $productCategories->templates,
                'category',
            ),
        );

        $this->assertCount(
            3,
            $productCategories->templateDefaultConfigs,
        );

        $this->assertSame(
            $categoryGridConfig,
            $productCategories
                ->templateDefaultConfigs['grid'],
        );

        $this->assertSame(
            $categoryCardsConfig,
            $productCategories
                ->templateDefaultConfigs['cards'],
        );

        $this->assertSame(
            $categoryCarouselConfig,
            $productCategories
                ->templateDefaultConfigs['carousel'],
        );

        /*
        |--------------------------------------------------------------------------
        | Product Collection
        |--------------------------------------------------------------------------
        */

        $productCollection = $definitions[3];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $productCollection,
        );

        $this->assertSame(
            SectionType::ProductCollection->value,
            $productCollection->type,
        );

        $this->assertSame(
            'Product Collection',
            $productCollection->label,
        );

        $this->assertSame(
            'grid',
            $productCollection->defaultTemplate,
        );

        $collectionGridConfig = [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ];

        $collectionCardsConfig = [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 3,
        ];

        $collectionCarouselConfig = [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'fade',
        ];

        $this->assertSame(
            $collectionGridConfig,
            $productCollection->defaultConfig,
        );

        $this->assertCount(
            3,
            $productCollection->templates,
        );

        $this->assertSame(
            [
                'grid',
                'cards',
                'carousel',
            ],
            array_column(
                $productCollection->templates,
                'key',
            ),
        );

        $this->assertSame(
            [
                'Products',
                'Products',
                'Products',
            ],
            array_column(
                $productCollection->templates,
                'category',
            ),
        );

        $this->assertCount(
            3,
            $productCollection->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'grid',
            $productCollection->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'cards',
            $productCollection->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'carousel',
            $productCollection->templateDefaultConfigs,
        );

        $this->assertSame(
            $collectionGridConfig,
            $productCollection
                ->templateDefaultConfigs['grid'],
        );

        $this->assertSame(
            $collectionCardsConfig,
            $productCollection
                ->templateDefaultConfigs['cards'],
        );

        $this->assertSame(
            $collectionCarouselConfig,
            $productCollection
                ->templateDefaultConfigs['carousel'],
        );

        /*
        |--------------------------------------------------------------------------
        | Promotional Banner
        |--------------------------------------------------------------------------
        */

        $promotionalBanner = $definitions[4];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $promotionalBanner,
        );

        $this->assertSame(
            SectionType::PromotionalBanner->value,
            $promotionalBanner->type,
        );

        $this->assertSame(
            'Promotional Banner',
            $promotionalBanner->label,
        );

        $this->assertSame(
            'image_banner',
            $promotionalBanner->defaultTemplate,
        );

        $this->assertCount(
            3,
            $promotionalBanner->templates,
        );

        $this->assertSame(
            [
                'image_banner',
                'content_banner',
                'split_banner',
            ],
            array_column(
                $promotionalBanner->templates,
                'key',
            ),
        );

        $this->assertSame(
            [
                'Image Banner',
                'Content Banner',
                'Split Banner',
            ],
            array_column(
                $promotionalBanner->templates,
                'label',
            ),
        );

        $this->assertSame(
            [
                'Marketing',
                'Marketing',
                'Marketing',
            ],
            array_column(
                $promotionalBanner->templates,
                'category',
            ),
        );

        $imageBannerConfig = [
            'heading' => 'Special Offer',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'center',
        ];

        $contentBannerConfig = [
            'heading' => 'Special Offer',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'center',
        ];

        $splitBannerConfig = [
            'heading' => 'Special Offer',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'left',
        ];

        $this->assertSame(
            $imageBannerConfig,
            $promotionalBanner->defaultConfig,
        );

        $this->assertCount(
            3,
            $promotionalBanner->templateDefaultConfigs,
        );

        $this->assertSame(
            $imageBannerConfig,
            $promotionalBanner
                ->templateDefaultConfigs['image_banner'],
        );

        $this->assertSame(
            $contentBannerConfig,
            $promotionalBanner
                ->templateDefaultConfigs['content_banner'],
        );

        $this->assertSame(
            $splitBannerConfig,
            $promotionalBanner
                ->templateDefaultConfigs['split_banner'],
        );

    }
}
