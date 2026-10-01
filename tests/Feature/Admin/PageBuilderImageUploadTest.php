<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PageBuilderImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_page_builder_image(): void
    {
        $page = Page::factory()->create();

        $response = $this->post(
            route(
                'admin.pages.builder.images.store',
                $page,
            ),
            [
                'image' => UploadedFile::fake()->image(
                    'hero.jpg',
                ),
            ],
        );

        $response->assertRedirect();
    }

    public function test_authorized_admin_can_upload_page_builder_image(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create();

        $this->actingAs(
            $admin,
            'admin',
        );

        $page = Page::factory()->create();

        $response = $this->postJson(
            route(
                'admin.pages.builder.images.store',
                $page,
            ),
            [
                'image' => UploadedFile::fake()->image(
                    'hero.jpg',
                    1920,
                    800,
                ),
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'image.original_name',
                'hero.jpg',
            )
            ->assertJsonPath(
                'image.mime_type',
                'image/jpeg',
            )
            ->assertJsonPath(
                'image.width',
                1920,
            )
            ->assertJsonPath(
                'image.height',
                800,
            )
            ->assertJsonStructure([
                'image' => [
                    'path',
                    'url',
                    'original_name',
                    'mime_type',
                    'file_size',
                    'width',
                    'height',
                ],
            ]);

        $path = $response->json(
            'image.path',
        );

        $this->assertIsString($path);

        Storage::disk('public')
            ->assertExists($path);

        $this->assertStringStartsWith(
            sprintf(
                'page-builder/pages/%d/images/',
                $page->id,
            ),
            $path,
        );
    }

    public function test_page_builder_image_is_required(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs(
            $admin,
            'admin',
        );

        $page = Page::factory()->create();

        $this
            ->postJson(
                route(
                    'admin.pages.builder.images.store',
                    $page,
                ),
                [],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'image',
            );
    }

    public function test_page_builder_image_rejects_unsupported_file_type(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs(
            $admin,
            'admin',
        );

        $page = Page::factory()->create();

        $this
            ->postJson(
                route(
                    'admin.pages.builder.images.store',
                    $page,
                ),
                [
                    'image' => UploadedFile::fake()->create(
                        'document.pdf',
                        100,
                        'application/pdf',
                    ),
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'image',
            );
    }

    public function test_page_builder_image_rejects_file_larger_than_five_megabytes(): void
    {

        $admin = Admin::factory()->create();

        $this->actingAs(
            $admin,
            'admin',
        );

        $page = Page::factory()->create();

        $this
            ->postJson(
                route(
                    'admin.pages.builder.images.store',
                    $page,
                ),
                [
                    'image' => UploadedFile::fake()->image(
                        'hero.jpg',
                    )->size(5121),
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'image',
            );
    }
}
