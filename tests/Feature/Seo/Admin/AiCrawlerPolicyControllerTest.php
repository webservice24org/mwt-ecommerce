<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AiCrawlerPolicyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_policy_center_renders_current_policy_history_and_platform_notes(): void
    {
        $this->withoutMiddleware();

        $this
            ->get(
                route(
                    'admin.seo.generative-search.crawlers.index',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/Seo/GenerativeSearch/CrawlerPolicy',
                    )
                    ->where(
                        'policy.allow_oai_search_bot',
                        true,
                    )
                    ->where(
                        'policy.allow_gpt_bot',
                        true,
                    )
                    ->where(
                        'policy.allow_google_extended',
                        true,
                    )
                    ->where(
                        'policy.version',
                        1,
                    )
                    ->where(
                        'history',
                        [],
                    )
                    ->has(
                        'platformNotes.oai_search_bot',
                    )
                    ->has(
                        'platformNotes.gpt_bot',
                    )
                    ->has(
                        'platformNotes.google_extended',
                    )
                    ->has(
                        'platformNotes.chatgpt_user',
                    )
                    ->has(
                        'platformNotes.shared_training_token',
                    ),
            );
    }

    public function test_admin_can_update_each_crawler_purpose_independently(): void
    {
        $this->withoutMiddleware();

        $this
            ->put(
                route(
                    'admin.seo.generative-search.crawlers.update',
                ),
                [
                    'allow_oai_search_bot' => false,

                    'allow_gpt_bot' => true,

                    'allow_google_extended' => false,

                    'version' => 1,
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $policy =
            app(
                AiCrawlerPolicyService::class,
            )->get();

        self::assertFalse(
            $policy->allowOaiSearchBot,
        );

        self::assertTrue(
            $policy->allowGptBot,
        );

        self::assertFalse(
            $policy->allowGoogleExtended,
        );

        self::assertSame(
            2,
            $policy->version,
        );

        self::assertDatabaseCount(
            'ai_crawler_policy_events',
            1,
        );
    }

    public function test_stale_admin_form_is_rejected_instead_of_overwriting_newer_policy(): void
    {
        $this->withoutMiddleware();

        $service =
            app(
                AiCrawlerPolicyService::class,
            );

        $initial =
            $service->get();

        $service->save(
            allowOaiSearchBot: false,

            allowGptBot: true,

            allowGoogleExtended: true,

            expectedVersion: $initial->version,
        );

        $this
            ->from(
                route(
                    'admin.seo.generative-search.crawlers.index',
                ),
            )
            ->put(
                route(
                    'admin.seo.generative-search.crawlers.update',
                ),
                [
                    'allow_oai_search_bot' => true,

                    'allow_gpt_bot' => false,

                    'allow_google_extended' => false,

                    'version' => 1,
                ],
            )
            ->assertRedirect(
                route(
                    'admin.seo.generative-search.crawlers.index',
                ),
            )
            ->assertSessionHasErrors(
                'version',
            );

        $current =
            $service->get();

        self::assertFalse(
            $current->allowOaiSearchBot,
        );

        self::assertTrue(
            $current->allowGptBot,
        );

        self::assertTrue(
            $current->allowGoogleExtended,
        );
    }
}
