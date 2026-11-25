<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SeoTechnicalHealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_technical_health_dashboard(): void
    {
        $this->withoutMiddleware();

        $response =
            $this->get(
                route(
                    'admin.seo.technical-health',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/TechnicalHealth',
                    )
                    ->has(
                        'health.checks',
                        14,
                    )
                    ->has(
                        'health.categories',
                        4,
                    )
                    ->has(
                        'health.coverage.by_type',
                        4,
                    )
                    ->has(
                        'health.operations.request_statuses_30d',
                        5,
                    )
                    ->has(
                        'health.operations.enrichment_statuses_30d',
                        3,
                    ),
            );
    }
}
