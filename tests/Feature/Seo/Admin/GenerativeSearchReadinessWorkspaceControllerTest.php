<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class GenerativeSearchReadinessWorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_workspace_renders_read_only_operational_review(): void
    {
        $this->withoutMiddleware();

        Product::factory()
            ->published()
            ->create([
                'name' => 'Workspace Product',

                'slug' => 'workspace-product',
            ]);

        $this
            ->get(
                route(
                    'admin.seo.generative-search.readiness',
                    [
                        'entity_type' => 'product',

                        'search' => 'Workspace',
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/GenerativeSearch/Readiness',
                    )
                    ->where(
                        'filters.entity_type',
                        'product',
                    )
                    ->where(
                        'filters.search',
                        'Workspace',
                    )
                    ->has(
                        'summary.scanned',
                    )
                    ->has(
                        'summary.ready',
                    )
                    ->has(
                        'summary.needs_work',
                    )
                    ->has(
                        'summary.blocked',
                    )
                    ->has(
                        'rows',
                    )
                    ->has(
                        'pagination',
                    ),
            );
    }
}
