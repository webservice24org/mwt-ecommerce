<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoBulkPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_route_persists_only_dry_run_state(): void
    {
        $this->withoutMiddleware();

        $product =
            Product::factory()
                ->create();

        $this
            ->post(
                route(
                    'admin.seo.bulk.plans.store',
                ),
                [
                    'targets' => [
                        [
                            'entity_type' => 'product',
                            'entity_id' => $product->id,
                        ],
                    ],
                ],
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        self::assertDatabaseCount(
            'ai_seo_bulk_jobs',
            1,
        );

        self::assertDatabaseCount(
            'ai_seo_bulk_job_items',
            1,
        );

        self::assertDatabaseCount(
            'ai_seo_requests',
            0,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestions',
            0,
        );
    }
}
