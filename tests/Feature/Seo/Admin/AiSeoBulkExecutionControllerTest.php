<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Jobs\Seo\AiSeoBulkItemExecutionJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AiSeoBulkExecutionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_detail_renders_execution_contract(): void
    {
        $this->withoutMiddleware();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Bulk Detail Product',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $this
            ->get(
                route(
                    'admin.seo.bulk.plans.show',
                    [
                        'bulkJobId' => $plan->id,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Bulk/Show',
                    )
                    ->where(
                        'plan.reference',
                        $plan->reference,
                    )
                    ->where(
                        'plan.status',
                        'planned',
                    )
                    ->where(
                        'plan.actions.can_execute',
                        true,
                    )
                    ->where(
                        'plan.actions.can_apply',
                        false,
                    )
                    ->has(
                        'plan.items',
                        1,
                    ),
            );
    }

    public function test_execute_route_queues_plan_items_without_running_provider_inline(): void
    {
        $this->withoutMiddleware();

        Queue::fake();

        $product =
            Product::factory()
                ->create();

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $this
            ->post(
                route(
                    'admin.seo.bulk.plans.execute',
                    [
                        'bulkJobId' => $plan->id,
                    ],
                ),
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $item =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->firstOrFail();

        self::assertSame(
            AiSeoBulkItemExecutionStatus::Queued,
            $item
                ->execution_status,
        );

        self::assertDatabaseCount(
            'ai_seo_requests',
            0,
        );

        Queue::assertPushed(
            AiSeoBulkItemExecutionJob::class,
            1,
        );
    }
}
