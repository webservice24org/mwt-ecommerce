<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Domain\Seo\GenerativeSearch\Queries\GetGenerativeSearchReadinessWorkspaceQuery;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetGenerativeSearchReadinessWorkspaceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_reviews_only_published_products_and_can_filter_blocked_state(): void
    {
        $blocked =
            Product::factory()
                ->published()
                ->create([
                    'name' => 'Blocked Generative Product',

                    'slug' => 'blocked-generative-product',

                    'short_description' => str_repeat(
                        'Useful product detail. ',
                        12,
                    ),

                    'meta_description' => 'A complete product description for readiness review.',
                ]);

        $blocked
            ->seoMeta()
            ->create([
                'robots_index' => false,

                'robots_follow' => true,
            ]);

        Product::factory()
            ->create([
                'name' => 'Draft Invisible Product',

                'slug' => 'draft-invisible-product',
            ]);

        $workspace =
            app(
                GetGenerativeSearchReadinessWorkspaceQuery::class,
            )->handle(
                entityType: 'product',

                status: 'blocked',

                search: 'Blocked Generative',
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
                'blocked'
            ],
        );

        self::assertCount(
            1,
            $workspace[
                'rows'
            ],
        );

        self::assertSame(
            $blocked->id,
            $workspace[
                'rows'
            ][
                0
            ][
                'entity_id'
            ],
        );

        self::assertSame(
            'blocked',
            $workspace[
                'rows'
            ][
                0
            ][
                'status'
            ],
        );

        self::assertFalse(
            $workspace[
                'rows'
            ][
                0
            ][
                'eligible_for_indexing'
            ],
        );

        self::assertSame(
            '/products/blocked-generative-product',
            $workspace[
                'rows'
            ][
                0
            ][
                'public_path'
            ],
        );
    }

    public function test_workspace_normalizes_page_size_and_reports_bounded_scan_contract(): void
    {
        Product::factory()
            ->published()
            ->count(3)
            ->create();

        $workspace =
            app(
                GetGenerativeSearchReadinessWorkspaceQuery::class,
            )->handle(
                entityType: 'product',

                perPage: 999,
            );

        self::assertSame(
            25,
            $workspace[
                'filters'
            ][
                'per_page'
            ],
        );

        self::assertSame(
            GetGenerativeSearchReadinessWorkspaceQuery::SCAN_LIMIT,
            $workspace[
                'summary'
            ][
                'scan_limit'
            ],
        );

        self::assertFalse(
            $workspace[
                'summary'
            ][
                'truncated'
            ],
        );

        self::assertArrayNotHasKey(
            'score',
            $workspace[
                'summary'
            ],
        );
    }
}
