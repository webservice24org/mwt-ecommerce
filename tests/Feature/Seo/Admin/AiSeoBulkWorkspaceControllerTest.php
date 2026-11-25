<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AiSeoBulkWorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_bulk_ai_dry_run_workspace(): void
    {
        $this->withoutMiddleware();

        Product::factory()
            ->create([
                'name' => 'Bulk Workspace Product',
            ]);

        $this
            ->get(
                route(
                    'admin.seo.bulk.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Bulk/Index',
                    )
                    ->where(
                        'workspace.summary.total',
                        1,
                    )
                    ->where(
                        'workspace.summary.ready',
                        1,
                    )
                    ->has(
                        'workspace.options.entity_types',
                        4,
                    )
                    ->has(
                        'workspace.recent_plans',
                        0,
                    ),
            );
    }
}
