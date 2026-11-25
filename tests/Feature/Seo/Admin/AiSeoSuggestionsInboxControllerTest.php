<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AiSeoSuggestionsInboxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_ai_suggestions_inbox(): void
    {
        $this->withoutMiddleware();

        $response =
            $this->get(
                route(
                    'admin.seo.suggestions.index',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/Suggestions/Index',
                    )
                    ->where(
                        'inbox.summary.total',
                        0,
                    )
                    ->has(
                        'inbox.options.entity_types',
                        5,
                    )
                    ->has(
                        'inbox.options.review_statuses',
                        4,
                    ),
            );
    }
}
