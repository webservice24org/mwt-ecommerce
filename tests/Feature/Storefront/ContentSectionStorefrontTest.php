<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ContentSectionStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_all_content_templates(): void
    {
        $page = Page::factory()->create([
            'title' => 'About Our Store',
            'slug' => 'about-our-store',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $text = $page
            ->sections()
            ->create([
                'type' => SectionType::Content,
                'template' => 'text',
                'position' => 10,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Our Story',
                    'body' => 'We started with a simple idea.',
                    'image' => null,
                    'image_alt' => null,
                    'alignment' => 'left',
                ],
            ]);

        $imageText = $page
            ->sections()
            ->create([
                'type' => SectionType::Content,
                'template' => 'image_text',
                'position' => 20,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Quality First',
                    'body' => 'Every product is selected with care.',
                    'image' => '/storage/page-builder/content/quality.webp',
                    'image_alt' => 'Products selected for quality',
                    'alignment' => 'left',
                ],
            ]);

        $textImage = $page
            ->sections()
            ->create([
                'type' => SectionType::Content,
                'template' => 'text_image',
                'position' => 30,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Dependable Delivery',
                    'body' => 'We work to make delivery clear and dependable.',
                    'image' => '/storage/page-builder/content/delivery.webp',
                    'image_alt' => 'A packaged customer order',
                    'alignment' => 'right',
                ],
            ]);

        $centered = $page
            ->sections()
            ->create([
                'type' => SectionType::Content,
                'template' => 'centered_content',
                'position' => 40,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Shopping Made Simple',
                    'body' => 'Useful products, clear information, and a simple experience.',
                    'image' => '/storage/page-builder/content/simple.webp',
                    'image_alt' => 'Our online storefront',
                    'alignment' => 'center',
                ],
            ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->has(
                        'page.sections',
                        4,
                    )
                    ->where(
                        'page.sections.0.id',
                        $text->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        SectionType::Content->value,
                    )
                    ->where(
                        'page.sections.0.template',
                        'text',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Our Story',
                    )
                    ->where(
                        'page.sections.0.config.body',
                        'We started with a simple idea.',
                    )
                    ->where(
                        'page.sections.0.config.image',
                        null,
                    )
                    ->where(
                        'page.sections.0.config.image_alt',
                        null,
                    )
                    ->where(
                        'page.sections.0.config.alignment',
                        'left',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    )
                    ->where(
                        'page.sections.1.id',
                        $imageText->id,
                    )
                    ->where(
                        'page.sections.1.type',
                        SectionType::Content->value,
                    )
                    ->where(
                        'page.sections.1.template',
                        'image_text',
                    )
                    ->where(
                        'page.sections.1.config.image',
                        '/storage/page-builder/content/quality.webp',
                    )
                    ->where(
                        'page.sections.1.config.image_alt',
                        'Products selected for quality',
                    )
                    ->where(
                        'page.sections.1.data',
                        [],
                    )
                    ->where(
                        'page.sections.2.id',
                        $textImage->id,
                    )
                    ->where(
                        'page.sections.2.type',
                        SectionType::Content->value,
                    )
                    ->where(
                        'page.sections.2.template',
                        'text_image',
                    )
                    ->where(
                        'page.sections.2.config.image',
                        '/storage/page-builder/content/delivery.webp',
                    )
                    ->where(
                        'page.sections.2.config.image_alt',
                        'A packaged customer order',
                    )
                    ->where(
                        'page.sections.2.config.alignment',
                        'right',
                    )
                    ->where(
                        'page.sections.2.data',
                        [],
                    )
                    ->where(
                        'page.sections.3.id',
                        $centered->id,
                    )
                    ->where(
                        'page.sections.3.type',
                        SectionType::Content->value,
                    )
                    ->where(
                        'page.sections.3.template',
                        'centered_content',
                    )
                    ->where(
                        'page.sections.3.config.heading',
                        'Shopping Made Simple',
                    )
                    ->where(
                        'page.sections.3.config.image',
                        '/storage/page-builder/content/simple.webp',
                    )
                    ->where(
                        'page.sections.3.config.image_alt',
                        'Our online storefront',
                    )
                    ->where(
                        'page.sections.3.config.alignment',
                        'center',
                    )
                    ->where(
                        'page.sections.3.data',
                        [],
                    ),
            );
    }

    public function test_disabled_content_section_is_not_exposed_publicly(): void
    {
        $page = Page::factory()->create([
            'title' => 'Hidden Content',
            'slug' => 'hidden-content',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::Content,
                'template' => 'text',
                'position' => 10,
                'is_enabled' => false,
                'config' => [
                    'heading' => 'Hidden Heading',
                    'body' => 'This content must not be exposed publicly.',
                    'image' => null,
                    'image_alt' => null,
                    'alignment' => 'left',
                ],
            ]);

        $this
            ->get(
                route(
                    'frontend.pages.show',
                    $page->slug,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->has(
                        'page.sections',
                        0,
                    ),
            );
    }
}
