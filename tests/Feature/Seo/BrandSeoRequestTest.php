<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandSeoRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_update_without_seo_payload_preserves_existing_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $brand =
            Brand::factory()
                ->create();

        $brand
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/brands/original',

                'robots_index' => false,

                'robots_follow' => true,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.brands.update',
                    $brand,
                ),
                $this->validPayload(
                    $brand,
                ),
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,

                'canonical_url' => 'https://example.com/brands/original',

                'robots_index' => false,

                'robots_follow' => true,
            ],
        );
    }

    public function test_brand_update_with_seo_payload_persists_extended_metadata(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $brand =
            Brand::factory()
                ->create();

        $payload =
            $this->validPayload(
                $brand,
            );

        $payload['seo'] = [
            'canonical_url' => 'https://example.com/brands/custom',

            'robots_index' => false,

            'robots_follow' => true,

            'og_title' => 'Brand OG',

            'og_description' => 'Brand OG Description',

            'og_image' => 'https://example.com/brand-og.jpg',

            'twitter_title' => 'Brand X',

            'twitter_description' => 'Brand X Description',

            'twitter_image' => 'https://example.com/brand-x.jpg',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.brands.update',
                    $brand,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,

                'title' => null,

                'description' => null,

                'canonical_url' => 'https://example.com/brands/custom',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Brand OG',
            ],
        );
    }

    public function test_explicit_empty_brand_seo_removes_existing_override(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $brand =
            Brand::factory()
                ->create();

        $brand
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/brands/old',

                'robots_index' => false,
            ]);

        $payload =
            $this->validPayload(
                $brand,
            );

        $payload['seo'] = [
            'canonical_url' => null,

            'robots_index' => null,

            'robots_follow' => null,

            'og_title' => null,

            'og_description' => null,

            'og_image' => null,

            'twitter_title' => null,

            'twitter_description' => null,

            'twitter_image' => null,
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.brands.update',
                    $brand,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,
            ],
        );
    }

    public function test_brand_canonical_rejects_non_http_protocol(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $brand =
            Brand::factory()
                ->create();

        $payload =
            $this->validPayload(
                $brand,
            );

        $payload['seo'] = [
            'canonical_url' => 'ftp://example.com/brands/test',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.brands.update',
                    $brand,
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'seo.canonical_url',
            );
    }

    public function test_brand_seo_strings_are_trimmed(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $brand =
            Brand::factory()
                ->create();

        $payload =
            $this->validPayload(
                $brand,
            );

        $payload['seo'] = [
            'canonical_url' => ' https://example.com/brands/test ',

            'og_title' => '  Brand OG  ',
        ];

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.brands.update',
                    $brand,
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'seo_meta',
            [
                'seoable_type' => Brand::class,

                'seoable_id' => $brand->id,

                'canonical_url' => 'https://example.com/brands/test',

                'og_title' => 'Brand OG',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(
        Brand $brand,
    ): array {
        return [
            'name' => $brand->name,

            'slug' => $brand->slug,

            'description' => $brand->description,

            'position' => $brand->position,

            'is_active' => $brand->is_active,

            'meta_title' => $brand->meta_title,

            'meta_description' => $brand->meta_description,

            'remove_logo' => false,
        ];
    }
}
