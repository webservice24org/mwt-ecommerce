<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AiSeoEntityWorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_entity_seo_workspace(): void
    {
        $this->withoutMiddleware();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Controller Workspace Product',

                    'slug' => 'controller-workspace-product',
                ]);

        $response =
            $this->get(
                route(
                    'admin.seo.entities.show',
                    [
                        'entityType' => 'product',

                        'entityId' => $product->id,
                    ],
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Entities/Show',
                    )
                    ->where(
                        'workspace.entity.name',
                        'Controller Workspace Product',
                    )
                    ->where(
                        'workspace.entity.type',
                        'product',
                    )
                    ->where(
                        'workspace.summary.latest_score',
                        null,
                    )
                    ->has(
                        'workspace.analysis_history',
                        0,
                    )
                    ->has(
                        'workspace.suggestions',
                        0,
                    ),
            );
    }

    public function test_invalid_entity_type_returns_not_found(): void
    {
        $this->withoutMiddleware();

        $this->get(
            route(
                'admin.seo.entities.show',
                [
                    'entityType' => 'unknown',

                    'entityId' => 1,
                ],
            ),
        )->assertNotFound();
    }
}
