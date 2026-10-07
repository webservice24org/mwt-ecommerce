<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use Tests\TestCase;

final class GetSectionDefinitionsQueryTest extends TestCase
{
    public function test_it_returns_registered_section_definitions(): void
    {
        $definitions = app(
            GetSectionDefinitionsQuery::class,
        )->handle();

        $this->assertCount(
            10,
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

        $this->assertCount(
            3,
            $hero->templates,
        );

        $contentSlider = $hero->templates[0];

        $this->assertSame(
            'content_slider',
            $contentSlider['key'],
        );

        $this->assertSame(
            'Content Slider',
            $contentSlider['label'],
        );

        $this->assertSame(
            'Create a sliding hero with background colors or images, text content, optional buttons, and transition effects.',
            $contentSlider['description'],
        );

        $this->assertSame(
            'Hero',
            $contentSlider['category'],
        );

        $imageSlider = $hero->templates[1];

        $this->assertSame(
            'image_slider',
            $imageSlider['key'],
        );

        $this->assertSame(
            'Image Slider',
            $imageSlider['label'],
        );

        $this->assertSame(
            'Create a clean sliding hero using banner images with optional links and transition effects.',
            $imageSlider['description'],
        );

        $this->assertSame(
            'Hero',
            $imageSlider['category'],
        );

        $staticHero = $hero->templates[2];

        $this->assertSame(
            'static',
            $staticHero['key'],
        );

        $this->assertSame(
            'Static Hero',
            $staticHero['label'],
        );

        $this->assertSame(
            'Display a non-sliding hero with a background color or image, text content, and optional buttons.',
            $staticHero['description'],
        );

        $this->assertSame(
            'Hero',
            $staticHero['category'],
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

        $this->assertSame(
            $hero->defaultConfig,
            $contentSliderConfig,
        );

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

        $productGrid = $featuredProducts->templates[0];

        $this->assertSame(
            'grid',
            $productGrid['key'],
        );

        $this->assertSame(
            'Product Grid',
            $productGrid['label'],
        );

        $this->assertSame(
            'Display a curated selection of featured products in a responsive grid.',
            $productGrid['description'],
        );

        $this->assertSame(
            'Products',
            $productGrid['category'],
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
