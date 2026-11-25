<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Contracts\Seoable;
use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategorySeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_implements_shared_seo_contract(): void
    {
        $category =
            Category::factory()
                ->create();

        $this->assertInstanceOf(
            Seoable::class,
            $category,
        );

        $this->assertNull(
            $category
                ->seoMeta()
                ->first(),
        );
    }

    public function test_category_can_persist_extended_seo_overrides(): void
    {
        $category =
            Category::factory()
                ->create([
                    'meta_title' => 'Phones',

                    'meta_description' => 'Browse mobile phones.',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $category,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/category/phones',

                robotsIndex: true,

                robotsFollow: true,

                ogTitle: 'Phones Category',

                ogDescription: 'Browse phones and accessories.',

                ogImage: 'https://example.com/images/phones.jpg',

                twitterTitle: 'Phones',

                twitterDescription: 'Shop phones.',

                twitterImage: 'https://example.com/images/phones-x.jpg',
            ),
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/category/phones',

                'robots_index' => true,

                'robots_follow' => true,
            ],
        );

        $category->refresh();

        $this->assertSame(
            'Phones',
            $category->meta_title,
        );

        $this->assertSame(
            'Browse mobile phones.',
            $category->meta_description,
        );
    }

    public function test_category_meta_title_and_description_are_not_duplicated_into_seo_meta(): void
    {
        $category =
            Category::factory()
                ->create([
                    'meta_title' => 'Category Meta',

                    'meta_description' => 'Category Description',
                ]);

        app(
            EntitySeoRepository::class,
        )->save(
            $category,
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
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,

                'title' => null,

                'description' => null,

                'robots_index' => false,
            ],
        );
    }

    public function test_explicit_empty_category_seo_removes_override_row(): void
    {
        $category =
            Category::factory()
                ->create();

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $category,
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: 'https://example.com/category/custom',

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
            $category,
            EntitySeoData::empty(),
        );

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Category::class,

                'seoable_id' => $category->id,
            ],
        );
    }
}
