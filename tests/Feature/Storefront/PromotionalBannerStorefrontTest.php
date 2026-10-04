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

final class PromotionalBannerStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_all_promotional_banner_templates(): void
    {
        $page = Page::factory()->create([
            'title' => 'Promotions',
            'slug' => 'promotions',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $imageBanner = $page
            ->sections()
            ->create([
                'type' => SectionType::PromotionalBanner,
                'template' => 'image_banner',
                'position' => 10,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Image Sale',
                    'description' => 'Save on featured products.',
                    'image' => '/storage/page-builder/image-sale.webp',
                    'cta_label' => 'Shop Now',
                    'cta_url' => '/products',
                    'alignment' => 'center',
                ],
            ]);

        $contentBanner = $page
            ->sections()
            ->create([
                'type' => SectionType::PromotionalBanner,
                'template' => 'content_banner',
                'position' => 20,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Content Sale',
                    'description' => 'A text-only promotion.',
                    'image' => null,
                    'cta_label' => null,
                    'cta_url' => null,
                    'alignment' => 'right',
                ],
            ]);

        $splitBanner = $page
            ->sections()
            ->create([
                'type' => SectionType::PromotionalBanner,
                'template' => 'split_banner',
                'position' => 30,
                'is_enabled' => true,
                'config' => [
                    'heading' => 'Split Sale',
                    'description' => 'Content and image together.',
                    'image' => '/storage/page-builder/split-sale.webp',
                    'cta_label' => 'Explore',
                    'cta_url' => '/products?promotion=sale',
                    'alignment' => 'left',
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
                        3,
                    )
                    ->where(
                        'page.sections.0.id',
                        $imageBanner->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        SectionType::PromotionalBanner->value,
                    )
                    ->where(
                        'page.sections.0.template',
                        'image_banner',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Image Sale',
                    )
                    ->where(
                        'page.sections.0.config.image',
                        '/storage/page-builder/image-sale.webp',
                    )
                    ->where(
                        'page.sections.0.config.cta_label',
                        'Shop Now',
                    )
                    ->where(
                        'page.sections.0.config.cta_url',
                        '/products',
                    )
                    ->where(
                        'page.sections.0.config.alignment',
                        'center',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    )
                    ->where(
                        'page.sections.1.id',
                        $contentBanner->id,
                    )
                    ->where(
                        'page.sections.1.type',
                        SectionType::PromotionalBanner->value,
                    )
                    ->where(
                        'page.sections.1.template',
                        'content_banner',
                    )
                    ->where(
                        'page.sections.1.config.heading',
                        'Content Sale',
                    )
                    ->where(
                        'page.sections.1.config.image',
                        null,
                    )
                    ->where(
                        'page.sections.1.config.alignment',
                        'right',
                    )
                    ->where(
                        'page.sections.1.data',
                        [],
                    )
                    ->where(
                        'page.sections.2.id',
                        $splitBanner->id,
                    )
                    ->where(
                        'page.sections.2.type',
                        SectionType::PromotionalBanner->value,
                    )
                    ->where(
                        'page.sections.2.template',
                        'split_banner',
                    )
                    ->where(
                        'page.sections.2.config.heading',
                        'Split Sale',
                    )
                    ->where(
                        'page.sections.2.config.image',
                        '/storage/page-builder/split-sale.webp',
                    )
                    ->where(
                        'page.sections.2.config.cta_label',
                        'Explore',
                    )
                    ->where(
                        'page.sections.2.config.cta_url',
                        '/products?promotion=sale',
                    )
                    ->where(
                        'page.sections.2.config.alignment',
                        'left',
                    )
                    ->where(
                        'page.sections.2.data',
                        [],
                    ),
            );
    }

    public function test_disabled_promotional_banner_is_not_exposed_publicly(): void
    {
        $page = Page::factory()->create([
            'title' => 'Promotions',
            'slug' => 'promotions-disabled-banner',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::PromotionalBanner,
                'template' => 'content_banner',
                'position' => 10,
                'is_enabled' => false,
                'config' => [
                    'heading' => 'Hidden Offer',
                    'description' => '',
                    'image' => null,
                    'cta_label' => null,
                    'cta_url' => null,
                    'alignment' => 'center',
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
