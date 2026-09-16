<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductVideoType;
use App\Models\Admin;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProductMediaAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
            'is_active' => true,
        ]);
    }

    public function test_first_uploaded_image_becomes_featured(): void
    {
        $product = Product::factory()->create();

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.images.store',
                    $product,
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image(
                            'product.jpg',
                            1200,
                            1200,
                        ),
                    'alt_text' => 'Product image',
                ],
            )
            ->assertRedirect();

        $image = $product
            ->images()
            ->firstOrFail();

        $this->assertTrue(
            $image->is_primary,
        );

        $this->assertSame(
            0,
            $image->position,
        );

        $this->assertTrue(
            Storage::disk('public')->exists(
                $image->path,
            ),
            "Expected product image [{$image->path}] to exist on the public disk.",
        );

    }

    public function test_setting_featured_image_clears_previous_featured_image(): void
    {
        $product = Product::factory()->create();

        $first = ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'position' => 0,
            ]);

        $second = ProductImage::factory()
            ->create([
                'product_id' => $product->id,

                'position' => 1,
            ]);

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.images.featured',
                    [
                        $product,
                        $second,
                    ],
                ),
            )
            ->assertRedirect();

        $this->assertFalse(
            (bool) $first
                ->refresh()
                ->is_primary,
        );

        $this->assertTrue(
            (bool) $second
                ->refresh()
                ->is_primary,
        );
    }

    public function test_deleting_featured_image_promotes_next_image(): void
    {
        $product = Product::factory()->create();

        $first = ProductImage::factory()
            ->primary()
            ->create([
                'product_id' => $product->id,

                'position' => 0,
            ]);

        $second = ProductImage::factory()
            ->create([
                'product_id' => $product->id,

                'position' => 1,
            ]);

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->delete(
                route(
                    'admin.products.images.destroy',
                    [
                        $product,
                        $first,
                    ],
                ),
            )
            ->assertRedirect();

        $this->assertTrue(
            (bool) $second
                ->refresh()
                ->is_primary,
        );

        $this->assertSame(
            0,
            $second->position,
        );
    }

    public function test_image_from_another_product_cannot_be_modified(): void
    {
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $image = ProductImage::factory()->create([
            'product_id' => $otherProduct->id,
        ]);

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.images.update',
                    [
                        $product,
                        $image,
                    ],
                ),
                [
                    'alt_text' => 'Attack',
                ],
            )
            ->assertNotFound();
    }

    public function test_gallery_reorder_requires_complete_product_gallery(): void
    {
        $product = Product::factory()->create();

        $first = ProductImage::factory()->create([
            'product_id' => $product->id,
            'position' => 0,
        ]);

        ProductImage::factory()->create([
            'product_id' => $product->id,
            'position' => 1,
        ]);

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.images.reorder',
                    $product,
                ),
                [
                    'image_ids' => [
                        $first->id,
                    ],
                ],
            )
            ->assertSessionHasErrors(
                'image_ids',
            );
    }

    public function test_youtube_video_can_be_saved(): void
    {
        $product = Product::factory()->create();

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.video.store',
                    $product,
                ),
                [
                    'type' => 'youtube',

                    'url' => 'https://www.youtube.com/watch?v=test123',

                    'title' => 'Product demo',
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'product_videos',
            [
                'product_id' => $product->id,

                'type' => 'youtube',

                'title' => 'Product demo',
            ],
        );
    }

    public function test_invalid_youtube_domain_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.video.store',
                    $product,
                ),
                [
                    'type' => 'youtube',

                    'url' => 'https://example.com/video',
                ],
            )
            ->assertSessionHasErrors(
                'url',
            );
    }

    public function test_uploaded_video_requires_file(): void
    {
        $product = Product::factory()->create();

        $this
            ->actingAs(
                $this->admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.video.store',
                    $product,
                ),
                [
                    'type' => 'upload',
                ],
            )
            ->assertSessionHasErrors(
                'video',
            );
    }

    public function test_editor_can_upload_product_image(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
            'is_active' => true,
        ]);

        $product = Product::factory()->create();

        $this
            ->actingAs($editor, 'admin')
            ->post(
                route(
                    'admin.products.images.store',
                    $product,
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image('editor-product.jpg'),
                ],
            )
            ->assertRedirect();

        $this->assertSame(
            1,
            $product->images()->count(),
        );
    }

    public function test_hosted_product_video_requires_https(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->post(
                route(
                    'admin.products.video.store',
                    $product,
                ),
                [
                    'type' => ProductVideoType::Youtube->value,
                    'url' => 'http://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'title' => 'Product Video',
                ],
            );

        $response->assertSessionHasErrors(
            'url',
        );

        $this->assertDatabaseMissing(
            'product_videos',
            [
                'product_id' => $product->id,
            ],
        );
    }

    public function test_deceptive_youtube_domain_is_rejected(): void
    {
        $product = Product::factory()->create();

        $response = $this
            ->actingAs($this->admin, 'admin')
            ->post(
                route(
                    'admin.products.video.store',
                    $product,
                ),
                [
                    'type' => ProductVideoType::Youtube->value,
                    'url' => 'https://youtube.com.evil.example/watch?v=123',
                    'title' => 'Product Video',
                ],
            );

        $response->assertSessionHasErrors(
            'url',
        );

        $this->assertDatabaseMissing(
            'product_videos',
            [
                'product_id' => $product->id,
            ],
        );
    }

    public function test_https_vimeo_video_can_be_saved(): void
    {
        $product = Product::factory()->create();

        $this
            ->actingAs($this->admin, 'admin')
            ->post(
                route(
                    'admin.products.video.store',
                    $product,
                ),
                [
                    'type' => ProductVideoType::Vimeo->value,
                    'url' => 'https://vimeo.com/123456789',
                    'title' => 'Vimeo Product Video',
                ],
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'product_videos',
            [
                'product_id' => $product->id,
                'type' => ProductVideoType::Vimeo->value,
                'url' => 'https://vimeo.com/123456789',
            ],
        );
    }
}
