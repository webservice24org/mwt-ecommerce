<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Contracts\Seoable;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_implements_shared_seo_contract(): void
    {
        $page =
            Page::factory()
                ->create();

        $this->assertInstanceOf(
            Seoable::class,
            $page,
        );

        $this->assertNull(
            $page
                ->seoMeta()
                ->first(),
        );
    }

    public function test_page_can_persist_extended_seo_metadata(): void
    {
        $page =
            Page::factory()
                ->create([
                    'meta_title' => 'About Us',

                    'meta_description' => 'Learn about our company.',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $page,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/pages/about-us',

                robotsIndex: true,

                robotsFollow: true,

                ogTitle: 'About Our Company',

                ogDescription: 'Learn more about us.',

                ogImage: 'https://example.com/about-og.jpg',

                twitterTitle: 'About Us',

                twitterDescription: 'Learn more about us.',

                twitterImage: 'https://example.com/about-x.jpg',
            ),
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/pages/about-us',

                'robots_index' => true,

                'robots_follow' => true,
            ],
        );

        $page->refresh();

        $this->assertSame(
            'About Us',
            $page->meta_title,
        );

        $this->assertSame(
            'Learn about our company.',
            $page->meta_description,
        );
    }

    public function test_page_meta_fields_are_not_duplicated_into_shared_seo_meta(): void
    {
        $page =
            Page::factory()
                ->create([
                    'meta_title' => 'Page Meta',

                    'meta_description' => 'Page Description',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $page,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: null,

                robotsIndex: false,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,

                'title' => null,

                'description' => null,

                'robots_index' => false,
            ],
        );
    }

    public function test_explicit_empty_page_seo_removes_override_row(): void
    {
        $page =
            Page::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $page,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/pages/custom',

                robotsIndex: false,

                robotsFollow: true,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $repository->save(
            $page,
            EntitySeoData::empty(),
        );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Page::class,

                'seoable_id' => $page->id,
            ],
        );
    }
}
