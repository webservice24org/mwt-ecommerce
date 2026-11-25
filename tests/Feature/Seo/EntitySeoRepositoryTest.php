<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Data\EntitySeoData;
use App\Domain\Seo\Repositories\EntitySeoRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Fixtures\Seo\TestSeoableModel;
use Tests\TestCase;

final class EntitySeoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(
            'seo_test_entities',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'name',
                );
            },
        );
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(
            'seo_test_entities',
        );

        parent::tearDown();
    }

    public function test_missing_entity_metadata_returns_empty_typed_data(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'Test Product',
                ]);

        $seo =
            app(
                EntitySeoRepository::class,
            )->get(
                $entity,
            );

        $this->assertFalse(
            $seo->hasOverrides(),
        );

        $this->assertNull(
            $seo->title,
        );

        $this->assertNull(
            $seo->robotsIndex,
        );

        $this->assertNull(
            $seo->robotsFollow,
        );

        $this->assertDatabaseCount(
            'seo_meta',
            0,
        );
    }

    public function test_repository_can_create_entity_seo_metadata(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'SEO Product',
                ]);

        $saved =
            app(
                EntitySeoRepository::class,
            )->save(
                $entity,
                $this->seoData(),
            );

        $this->assertTrue(
            $saved->hasOverrides(),
        );

        $this->assertSame(
            'Custom SEO Title',
            $saved->title,
        );

        $this->assertFalse(
            $saved->robotsIndex,
        );

        $this->assertTrue(
            $saved->robotsFollow,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => TestSeoableModel::class,

                'seoable_id' => $entity->getKey(),

                'title' => 'Custom SEO Title',

                'canonical_url' => 'https://example.com/custom-url',

                'robots_index' => false,

                'robots_follow' => true,
            ],
        );
    }

    public function test_repository_updates_existing_metadata_without_creating_duplicate_row(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'SEO Product',
                ]);

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $entity,
            $this->seoData(),
        );

        $repository->save(
            $entity,
            new EntitySeoData(
                title: 'Updated SEO Title',

                description: 'Updated description.',

                canonicalUrl: null,

                robotsIndex: true,

                robotsFollow: true,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $this->assertDatabaseCount(
            'seo_meta',
            1,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => TestSeoableModel::class,

                'seoable_id' => $entity->getKey(),

                'title' => 'Updated SEO Title',

                'robots_index' => true,
            ],
        );
    }

    public function test_all_null_overrides_delete_existing_metadata_row(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'SEO Product',
                ]);

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $entity,
            $this->seoData(),
        );

        $this->assertDatabaseCount(
            'seo_meta',
            1,
        );

        $result =
            $repository->save(
                $entity,
                EntitySeoData::empty(),
            );

        $this->assertFalse(
            $result->hasOverrides(),
        );

        $this->assertDatabaseCount(
            'seo_meta',
            0,
        );
    }

    public function test_nullable_robots_fields_preserve_inheritance_semantics(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'Inherited SEO Product',
                ]);

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $entity,
            new EntitySeoData(
                title: 'Inherited Robots',

                description: null,

                canonicalUrl: null,

                robotsIndex: null,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $loaded =
            $repository->get(
                $entity,
            );

        $this->assertNull(
            $loaded->robotsIndex,
        );

        $this->assertNull(
            $loaded->robotsFollow,
        );

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => TestSeoableModel::class,

                'seoable_id' => $entity->getKey(),

                'title' => 'Inherited Robots',

                'robots_index' => null,

                'robots_follow' => null,
            ],
        );
    }

    public function test_metadata_is_isolated_between_different_entities(): void
    {
        $first =
            TestSeoableModel::query()
                ->create([
                    'name' => 'First',
                ]);

        $second =
            TestSeoableModel::query()
                ->create([
                    'name' => 'Second',
                ]);

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $first,
            new EntitySeoData(
                title: 'First SEO',

                description: null,

                canonicalUrl: null,

                robotsIndex: null,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $repository->save(
            $second,
            new EntitySeoData(
                title: 'Second SEO',

                description: null,

                canonicalUrl: null,

                robotsIndex: null,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            ),
        );

        $this->assertSame(
            'First SEO',
            $repository
                ->get(
                    $first,
                )
                ->title,
        );

        $this->assertSame(
            'Second SEO',
            $repository
                ->get(
                    $second,
                )
                ->title,
        );

        $this->assertDatabaseCount(
            'seo_meta',
            2,
        );
    }

    public function test_repository_delete_removes_entity_metadata(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'SEO Product',
                ]);

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $entity,
            $this->seoData(),
        );

        $repository->delete(
            $entity,
        );

        $this->assertDatabaseCount(
            'seo_meta',
            0,
        );
    }

    public function test_unsaved_entity_cannot_receive_seo_metadata(): void
    {
        $entity =
            new TestSeoableModel([
                'name' => 'Unsaved',
            ]);

        $this->expectException(
            LogicException::class,
        );

        $this->expectExceptionMessage(
            'SEO metadata cannot be saved for an unsaved entity.',
        );

        app(
            EntitySeoRepository::class,
        )->save(
            $entity,
            $this->seoData(),
        );
    }

    public function test_polymorphic_unique_constraint_prevents_duplicate_metadata(): void
    {
        $entity =
            TestSeoableModel::query()
                ->create([
                    'name' => 'SEO Product',
                ]);

        $repository =
            app(
                EntitySeoRepository::class,
            );

        $repository->save(
            $entity,
            $this->seoData(),
        );

        $this->assertDatabaseCount(
            'seo_meta',
            1,
        );

        $repository->save(
            $entity,
            $this->seoData(),
        );

        $this->assertDatabaseCount(
            'seo_meta',
            1,
        );
    }

    private function seoData(): EntitySeoData
    {
        return new EntitySeoData(
            title: 'Custom SEO Title',

            description: 'Custom SEO description.',

            canonicalUrl: 'https://example.com/custom-url',

            robotsIndex: false,

            robotsFollow: true,

            ogTitle: 'Custom OG Title',

            ogDescription: 'Custom OG description.',

            ogImage: 'https://example.com/og.jpg',

            twitterTitle: 'Twitter Title',

            twitterDescription: 'Twitter description.',

            twitterImage: 'https://example.com/twitter.jpg',
        );
    }
}
