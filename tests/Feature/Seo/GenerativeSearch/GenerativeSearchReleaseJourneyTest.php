<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Queries\GetGenerativeSearchReadinessWorkspaceQuery;
use App\Domain\Seo\GenerativeSearch\Services\AiCrawlerPolicyService;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GenerativeSearchReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_l1_through_l4_release_journey_uses_current_authoritative_state_without_persisting_readiness(): void
    {
        $brand =
            Brand::factory()
                ->create([
                    'name' => 'Release Brand',

                    'slug' => 'release-brand',

                    'description' => 'A real visible brand relationship for the release product.',

                    'is_active' => true,
                ]);

        $product =
            Product::factory()
                ->published()
                ->create([
                    'brand_id' => $brand->id,

                    'name' => 'Generative Release Product',

                    'slug' => 'generative-release-product',

                    'short_description' => str_repeat(
                        'Useful product fact for people and search systems. ',
                        8,
                    ),

                    'description' => str_repeat(
                        'This is visible factual product content with stable entity context. ',
                        8,
                    ),

                    'meta_description' => 'A clear factual description of the generative release product.',
                ]);

        $product
            ->seoMeta()
            ->create([
                'robots_index' => true,

                'robots_follow' => true,
            ]);

        $workspace =
            app(
                GetGenerativeSearchReadinessWorkspaceQuery::class,
            )->handle(
                entityType: 'product',

                search: 'Generative Release Product',
            );

        self::assertSame(
            1,
            $workspace[
                'summary'
            ][
                'scanned'
            ],
        );

        self::assertSame(
            1,
            $workspace[
                'summary'
            ][
                'ready'
            ],
        );

        self::assertCount(
            1,
            $workspace[
                'rows'
            ],
        );

        self::assertSame(
            'ready',
            $workspace[
                'rows'
            ][
                0
            ][
                'status'
            ],
        );

        self::assertTrue(
            $workspace[
                'rows'
            ][
                0
            ][
                'eligible_for_indexing'
            ],
        );

        self::assertArrayNotHasKey(
            'score',
            $workspace[
                'rows'
            ][
                0
            ],
        );

        /*
         * L.4 is calculated from current state.
         * A real robots change immediately changes the L.1 result.
         */
        $product
            ->seoMeta()
            ->update([
                'robots_index' => false,
            ]);

        $blocked =
            app(
                GetGenerativeSearchReadinessWorkspaceQuery::class,
            )->handle(
                entityType: 'product',

                status: 'blocked',

                search: 'Generative Release Product',
            );

        self::assertSame(
            1,
            $blocked[
                'summary'
            ][
                'blocked'
            ],
        );

        self::assertSame(
            'blocked',
            $blocked[
                'rows'
            ][
                0
            ][
                'status'
            ],
        );

        self::assertFalse(
            $blocked[
                'rows'
            ][
                0
            ][
                'eligible_for_indexing'
            ],
        );

        /*
         * No L.1/L.4 readiness-history table is created.
         * Readiness remains a current-state diagnostic.
         */
        self::assertFalse(
            \Schema::hasTable(
                'generative_search_readiness_results',
            ),
        );

        self::assertFalse(
            \Schema::hasTable(
                'generative_search_scores',
            ),
        );
    }

    public function test_l2_crawler_policy_changes_only_managed_ai_crawler_groups_and_preserves_generic_robots_rules(): void
    {
        $service =
            app(
                AiCrawlerPolicyService::class,
            );

        $service->forget();

        $initial =
            $service->get();

        $service->save(
            allowOaiSearchBot: false,

            allowGptBot: true,

            allowGoogleExtended: false,

            expectedVersion: $initial->version,
        );

        $response =
            $this
                ->get(
                    '/robots.txt',
                )
                ->assertOk();

        $content =
            $response
                ->getContent();

        self::assertIsString(
            $content,
        );

        self::assertStringContainsString(
            "User-agent: *\n",
            $content,
        );

        self::assertStringContainsString(
            "Disallow: /admin\n",
            $content,
        );

        self::assertStringContainsString(
            "Disallow: /account\n",
            $content,
        );

        self::assertStringContainsString(
            "User-agent: OAI-SearchBot\nDisallow: /",
            $content,
        );

        self::assertStringNotContainsString(
            "User-agent: GPTBot\nDisallow: /",
            $content,
        );

        self::assertStringContainsString(
            "User-agent: Google-Extended\nDisallow: /",
            $content,
        );

        self::assertSame(
            1,
            substr_count(
                $content,
                '# BEGIN MWT AI CRAWLER POLICY',
            ),
        );

        self::assertDatabaseCount(
            'ai_crawler_policy_events',
            1,
        );
    }
}
