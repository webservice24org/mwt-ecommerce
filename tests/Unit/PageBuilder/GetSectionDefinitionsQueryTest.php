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
            3,
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

    }
}
