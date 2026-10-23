<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Admin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ProductDescriptionEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tiptap-editor.image_upload.enabled' => false,
            'tiptap-editor.image_upload.disk' => 'public',
            'tiptap-editor.image_upload.temporary_directory' => 'editor/tmp',
            'tiptap-editor.image_upload.permanent_directory' => 'editor/images',
        ]);
    }

    public function test_guest_cannot_upload_product_description_media(): void
    {
        Storage::fake('public');

        $this
            ->post(
                route(
                    'admin.products.description-media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image('product.jpg'),
                ],
            )
            ->assertRedirect(
                route('admin.login'),
            );
    }

    public function test_authenticated_admin_can_upload_temporary_product_description_image(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.description-media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image(
                            'product.jpg',
                            1200,
                            800,
                        ),
                ],
                [
                    'Accept' => 'application/json',
                ],
            );

        $response
            ->assertOk()
            ->assertJson([
                'temporary' => true,
            ]);

        $path = $response->json('path');
        $url = $response->json('url');

        $this->assertIsString($path);
        $this->assertIsString($url);

        $this->assertStringStartsWith(
            'editor/tmp/',
            $path,
        );

        $this->assertSame(
            '/storage/'.$path,
            $url,
        );

        Storage::disk('public')
            ->assertExists($path);
    }

    public function test_product_description_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.description-media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->create(
                            'payload.svg',
                            10,
                            'image/svg+xml',
                        ),
                ],
            )
            ->assertSessionHasErrors([
                'image',
            ]);
    }

    public function test_package_default_upload_endpoint_is_disabled(): void
    {
        Storage::fake('public');

        config([
            'tiptap-editor.image_upload.enabled' => false,
        ]);

        $this
            ->post(
                '/tiptap-editor/media',
                [
                    'image' => UploadedFile::fake()
                        ->image('blocked.jpg'),
                ],
            )
            ->assertForbidden();
    }

    public function test_product_description_is_sanitized_when_product_is_created(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $unsafeHtml = <<<'HTML'
<h2 onclick="alert('xss')">Safe heading</h2>
<p>Safe paragraph</p>
<a href="javascript:alert('xss')" target="_blank">Unsafe link</a>
<img src="/storage/example.jpg" onerror="alert('xss')" alt="Example">
<script>alert('xss')</script>
<iframe src="https://evil.example/embed/1"></iframe>
HTML;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.store',
                ),
                $this->productPayload([
                    'name' => 'Sanitized Product',
                    'sku' => 'SANITIZED-PRODUCT-001',
                    'description' => $unsafeHtml,
                ]),
            )
            ->assertRedirect();

        $product = Product::query()
            ->where(
                'name',
                'Sanitized Product',
            )
            ->firstOrFail();

        $description = (string) $product->description;

        $this->assertStringContainsString(
            '<h2>Safe heading</h2>',
            $description,
        );

        $this->assertStringContainsString(
            '<p>Safe paragraph</p>',
            $description,
        );

        $this->assertStringContainsString(
            'alt="Example"',
            $description,
        );

        $this->assertStringNotContainsString(
            'onclick=',
            $description,
        );

        $this->assertStringNotContainsString(
            'onerror=',
            $description,
        );

        $this->assertStringNotContainsString(
            'javascript:',
            $description,
        );

        $this->assertStringNotContainsString(
            '<script',
            $description,
        );

        $this->assertStringNotContainsString(
            '<iframe',
            $description,
        );
    }

    public function test_create_finalizes_temporary_description_media(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $temporaryPath =
            'editor/tmp/create-description.jpg';

        Storage::disk('public')->put(
            $temporaryPath,
            'image-bytes',
        );

        $description = sprintf(
            '<figure data-mw-image data-mw-path="%s">'
            .'<img src="/storage/%s" alt="Product image">'
            .'<figcaption>Product image</figcaption>'
            .'</figure>',
            $temporaryPath,
            $temporaryPath,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.products.store',
                ),
                $this->productPayload([
                    'name' => 'Create Media Product',

                    'sku' => 'CREATE-MEDIA-001',

                    'description' => $description,
                ]),
            )
            ->assertRedirect();

        $product = Product::query()
            ->where(
                'name',
                'Create Media Product',
            )
            ->firstOrFail();

        Storage::disk('public')
            ->assertMissing(
                $temporaryPath,
            );

        $directory =
            'editor/images/products/'
            .$product->id
            .'/description';

        $files = Storage::disk('public')
            ->files($directory);

        $this->assertCount(
            1,
            $files,
        );

        $permanentPath = $files[0];

        Storage::disk('public')
            ->assertExists(
                $permanentPath,
            );

        $storedDescription =
            (string) $product->description;

        $this->assertStringContainsString(
            'data-mw-path="'
            .$permanentPath
            .'"',
            $storedDescription,
        );

        $this->assertStringContainsString(
            'src="/storage/'
            .$permanentPath
            .'"',
            $storedDescription,
        );

        $this->assertStringNotContainsString(
            'editor/tmp/',
            $storedDescription,
        );
    }

    public function test_update_finalizes_new_temporary_description_media(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'sku' => 'UPDATE-MEDIA-001',
            'price' => 25000,
            'compare_at_price' => null,
            'cost_price' => null,
            'description' => '<p>Old description</p>',
        ]);

        $temporaryPath =
            'editor/tmp/update-description.jpg';

        Storage::disk('public')->put(
            $temporaryPath,
            'image-bytes',
        );

        $description = sprintf(
            '<p>Updated description</p>'
            .'<figure data-mw-image data-mw-path="%s">'
            .'<img src="/storage/%s" alt="Updated image">'
            .'</figure>',
            $temporaryPath,
            $temporaryPath,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $this->productPayload([
                    'name' => $product->name,

                    'slug' => $product->slug,

                    'sku' => 'UPDATE-MEDIA-001',

                    'description' => $description,
                ]),
            )
            ->assertRedirect();

        $product->refresh();

        Storage::disk('public')
            ->assertMissing(
                $temporaryPath,
            );

        $directory =
            'editor/images/products/'
            .$product->id
            .'/description';

        $files = Storage::disk('public')
            ->files($directory);

        $this->assertCount(
            1,
            $files,
        );

        $permanentPath = $files[0];

        $this->assertStringContainsString(
            'data-mw-path="'
            .$permanentPath
            .'"',
            (string) $product->description,
        );

        $this->assertStringContainsString(
            'src="/storage/'
            .$permanentPath
            .'"',
            (string) $product->description,
        );

        $this->assertStringNotContainsString(
            'editor/tmp/',
            (string) $product->description,
        );
    }

    public function test_already_permanent_description_media_is_not_moved_again(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'sku' => 'PERMANENT-MEDIA-001',
            'price' => 25000,
            'compare_at_price' => null,
            'cost_price' => null,
        ]);

        $permanentPath =
            'editor/images/products/'
            .$product->id
            .'/description/existing.jpg';

        Storage::disk('public')->put(
            $permanentPath,
            'image-bytes',
        );

        $description = sprintf(
            '<figure data-mw-image data-mw-path="%s">'
            .'<img src="/storage/%s" alt="Existing image">'
            .'</figure>',
            $permanentPath,
            $permanentPath,
        );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $this->productPayload([
                    'name' => $product->name,

                    'slug' => $product->slug,

                    'sku' => 'PERMANENT-MEDIA-001',

                    'description' => $description,
                ]),
            )
            ->assertRedirect();

        $product->refresh();

        Storage::disk('public')
            ->assertExists(
                $permanentPath,
            );

        $this->assertSame(
            $description,
            $product->description,
        );
    }

    public function test_storefront_receives_persisted_sanitized_description(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'sku' => 'STOREFRONT-RICH-001',
            'price' => 35000,
            'compare_at_price' => null,
            'cost_price' => null,
            'status' => ProductStatus::Published,
            'published_at' => now()
                ->subMinute(),
        ]);

        $unsafeHtml = <<<'HTML'
<h2 onclick="alert('xss')">Storefront heading</h2>
<p><strong>Rich storefront content</strong></p>
<a href="javascript:alert('xss')">Bad URL</a>
HTML;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.products.update',
                    $product,
                ),
                $this->productPayload([
                    'name' => $product->name,

                    'slug' => $product->slug,

                    'sku' => 'STOREFRONT-RICH-001',

                    'status' => ProductStatus::Published->value,

                    'published_at' => now()
                        ->subMinute()
                        ->format(
                            'Y-m-d H:i:s',
                        ),

                    'description' => $unsafeHtml,
                ]),
            )
            ->assertRedirect();

        $product->refresh();

        $storedDescription =
            (string) $product->description;

        $this->assertStringContainsString(
            'Storefront heading',
            $storedDescription,
        );

        $this->assertStringContainsString(
            '<strong>Rich storefront content</strong>',
            $storedDescription,
        );

        $this->assertStringNotContainsString(
            'onclick=',
            $storedDescription,
        );

        $this->assertStringNotContainsString(
            'javascript:',
            $storedDescription,
        );

        $this
            ->get(
                route(
                    'products.show',
                    $product->slug,
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Frontend/Products/Show',
                    )
                    ->where(
                        'product.description',
                        $storedDescription,
                    ),
            );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(
        array $overrides = [],
    ): array {
        return array_replace(
            [
                'brand_id' => null,

                'type' => ProductType::Simple->value,

                'sku' => 'TIPTAP-PRODUCT-001',

                'price' => 25000,

                'compare_at_price' => null,

                'cost_price' => null,

                'name' => 'Tiptap Product',

                'slug' => '',

                'short_description' => 'Short description',

                'description' => '<p>Product description</p>',

                'status' => ProductStatus::Draft->value,

                'is_featured' => false,

                'position' => 0,

                'published_at' => null,

                'meta_title' => null,

                'meta_description' => null,

                'category_ids' => [],
            ],
            $overrides,
        );
    }
}
