<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_manage_products_except_delete(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $product =
            Product::factory()->create();

        $policy = app(
            ProductPolicy::class,
        );

        $this->assertTrue(
            $policy->viewAny($admin),
        );

        $this->assertTrue(
            $policy->create($admin),
        );

        $this->assertTrue(
            $policy->update(
                $admin,
                $product,
            ),
        );

        $this->assertFalse(
            $policy->delete(
                $admin,
                $product,
            ),
        );
    }

    public function test_manager_can_delete_products(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $product =
            Product::factory()->create();

        $this->assertTrue(
            app(ProductPolicy::class)
                ->delete(
                    $admin,
                    $product,
                ),
        );
    }

    public function test_super_admin_has_full_product_access(): void
    {
        $admin = Admin::factory()
            ->superAdmin()
            ->create();

        $product =
            Product::factory()->create();

        $policy = app(
            ProductPolicy::class,
        );

        $this->assertTrue(
            $policy->before(
                $admin,
                'delete',
            ),
        );

        $this->assertTrue(
            $policy->before(
                $admin,
                'update',
            ),
        );
    }
}
