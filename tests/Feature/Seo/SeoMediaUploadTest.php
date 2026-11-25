<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SeoMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(
            'public',
        );
    }

    public function test_guest_cannot_upload_seo_media(): void
    {
        $this
            ->post(
                route(
                    'admin.seo.media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image(
                            'social.jpg',
                            1200,
                            630,
                        ),
                ],
            )
            ->assertRedirect();
    }

    public function test_authenticated_admin_can_upload_seo_image(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->post(
                    route(
                        'admin.seo.media.store',
                    ),
                    [
                        'image' => UploadedFile::fake()
                            ->image(
                                'social.jpg',
                                1200,
                                630,
                            ),
                    ],
                );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'image' => [
                    'path',
                    'url',
                ],
            ]);

        $path =
            $response->json(
                'image.path',
            );
        $url =
            $response->json(
                'image.url',
            );

        $this->assertIsString(
            $url,
        );

        $this->assertTrue(
            str_starts_with(
                $url,
                'http://',
            ) ||
            str_starts_with(
                $url,
                'https://',
            ),
        );

        $this->assertStringContainsString(
            '/storage/seo/social/',
            $url,
        );

        $this->assertIsString(
            $path,
        );

        $this->assertStringStartsWith(
            'seo/social/',
            $path,
        );

        Storage::disk(
            'public',
        )->assertExists(
            $path,
        );
    }

    public function test_non_image_file_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.seo.media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->create(
                            'malicious.php',
                            10,
                            'application/x-php',
                        ),
                ],
            )
            ->assertSessionHasErrors(
                'image',
            );
    }

    public function test_oversized_image_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.seo.media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image(
                            'large.jpg',
                            1200,
                            630,
                        )
                        ->size(
                            6000,
                        ),
                ],
            )
            ->assertSessionHasErrors(
                'image',
            );
    }

    public function test_tiny_social_image_is_rejected(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,
                ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.seo.media.store',
                ),
                [
                    'image' => UploadedFile::fake()
                        ->image(
                            'tiny.jpg',
                            100,
                            100,
                        ),
                ],
            )
            ->assertSessionHasErrors(
                'image',
            );
    }

    public function test_inactive_admin_cannot_upload_seo_media(): void
    {
        $admin =
            Admin::factory()
                ->create([
                    'role' => AdminRole::Admin,

                    'is_active' => false,
                ]);

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->post(
                    route(
                        'admin.seo.media.store',
                    ),
                    [
                        'image' => UploadedFile::fake()
                            ->image(
                                'social.jpg',
                                1200,
                                630,
                            ),
                    ],
                );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors();

        $errors =
            session(
                'errors',
            );

        $this->assertNotNull(
            $errors,
        );

        $this->assertStringContainsString(
            'Your administrator account is currently unavailable.',
            $errors
                ->getBag(
                    'default',
                )
                ->first(),
        );
    }
}
