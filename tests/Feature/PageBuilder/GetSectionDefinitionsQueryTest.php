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
            2,
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
    }
}
