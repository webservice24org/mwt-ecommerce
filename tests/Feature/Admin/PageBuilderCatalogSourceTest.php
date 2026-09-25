<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageBuilderCatalogSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search_builder_products(): void
    {
        $page = Page::factory()->create();

        $response = $this->getJson(
            route(
                'admin.pages.builder.products',
                [
                    'page' => $page,
                    'search' => 'shirt',
                ],
            ),
        );

        $response->assertUnauthorized();
    }

    public function test_authorized_admin_can_search_builder_products(): void
    {
        $admin = Admin::factory()->create();

        $page = Page::factory()->create();

        $product = Product::factory()->create([
            'name' => 'Classic Shirt',
            'slug' => 'classic-shirt',
            'sku' => 'SHIRT-001',
        ]);

        Product::factory()->create([
            'name' => 'Leather Wallet',
            'slug' => 'leather-wallet',
            'sku' => 'WALLET-001',
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->getJson(
                route(
                    'admin.pages.builder.products',
                    [
                        'page' => $page,
                        'search' => 'shirt',
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertJson([
                'products' => [
                    [
                        'id' => $product->id,
                        'name' => 'Classic Shirt',
                        'slug' => 'classic-shirt',
                        'sku' => 'SHIRT-001',
                    ],
                ],
            ]);
    }

    public function test_product_search_requires_search_term(): void
    {
        $admin = Admin::factory()->create();

        $page = Page::factory()->create();

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->getJson(
                route(
                    'admin.pages.builder.products',
                    [
                        'page' => $page,
                    ],
                ),
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'search',
            ]);
    }

    public function test_product_search_does_not_expose_internal_fields(): void
    {
        $admin = Admin::factory()->create();

        $page = Page::factory()->create();

        Product::factory()->create([
            'name' => 'Private Fields Test',
            'slug' => 'private-fields-test',
            'sku' => 'PRIVATE-001',
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->getJson(
                route(
                    'admin.pages.builder.products',
                    [
                        'page' => $page,
                        'search' => 'Private',
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'products' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'sku',
                    ],
                ],
            ]);

        $product =
            $response->json('products.0');

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
                'sku',
            ],
            array_keys($product),
        );
    }
}
