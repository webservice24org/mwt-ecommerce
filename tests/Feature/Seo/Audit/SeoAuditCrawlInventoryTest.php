<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Data\SeoAuditCrawlPageData;
use App\Domain\Seo\Audit\Data\SeoAuditInternalLinkData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Queries\SeoAuditCrawlInventorySummaryQuery;
use App\Domain\Seo\Audit\Services\SeoAuditCrawlInventoryRecorder;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAuditCrawlInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_and_internal_link_graph_are_normalized_bounded_and_idempotent(): void
    {
        $admin = Admin::factory()->create();

        $lifecycle =
            app(SeoAuditRunLifecycle::class);

        $run =
            $lifecycle->start(
                $lifecycle->request($admin),
            );

        $recorder =
            app(
                SeoAuditCrawlInventoryRecorder::class,
            );

        $page =
            $recorder->recordPage(
                run: $run,
                canonicalHost: 'https://Example.test',
                data: new SeoAuditCrawlPageData(
                    url: '/products/widget#reviews',
                    state: SeoAuditCrawlState::Fetched,
                    depth: 0,
                    subjectType: SeoAuditSubjectType::Product,
                    subjectKey: 'product:10',
                    httpStatus: 200,
                    contentType: 'text/html; charset=UTF-8',
                    canonicalUrl: '/products/widget',
                    isIndexable: true,
                    title: 'Widget',
                    metaDescription: '',
                    h1Count: 1,
                    wordCount: 240,
                ),
            );

        self::assertSame(
            'https://example.test/products/widget',
            $page->url,
        );

        self::assertSame(
            SeoAuditCrawlState::Fetched,
            $page->state,
        );

        /*
         * A later discovery must not downgrade a fetched node.
         * It may still improve the shortest known crawl depth.
         */
        $recorder->recordPage(
            run: $run,
            canonicalHost: 'https://example.test',
            data: new SeoAuditCrawlPageData(
                url: 'https://example.test/products/widget#top',
                state: SeoAuditCrawlState::Discovered,
                depth: 2,
            ),
        );

        $page->refresh();

        self::assertSame(
            SeoAuditCrawlState::Fetched,
            $page->state,
        );

        self::assertSame(
            0,
            $page->depth,
        );

        $link =
            $recorder->recordLink(
                run: $run,
                canonicalHost: 'https://example.test',
                data: new SeoAuditInternalLinkData(
                    sourceUrl: $page->url,
                    targetReference: '/category/cables#top',
                    sourceDepth: 0,
                    anchorText: 'Cables',
                    occurrences: 2,
                ),
            );

        self::assertNotNull($link);

        self::assertSame(
            'https://example.test/category/cables',
            $link->target_url,
        );

        self::assertSame(
            2,
            $run->crawlPages()->count(),
        );

        self::assertSame(
            1,
            $run->internalLinks()->count(),
        );

        /*
         * Re-recording the same source->target edge replaces
         * the aggregate observation rather than duplicating it.
         */
        $recorder->recordLink(
            run: $run,
            canonicalHost: 'https://example.test',
            data: new SeoAuditInternalLinkData(
                sourceUrl: $page->url,
                targetReference: '/category/cables',
                sourceDepth: 0,
                anchorText: 'Shop cables',
                occurrences: 3,
            ),
        );

        self::assertSame(
            1,
            $run->internalLinks()->count(),
        );

        self::assertSame(
            3,
            $run
                ->internalLinks()
                ->firstOrFail()
                ->occurrences,
        );

        $external =
            $recorder->recordLink(
                run: $run,
                canonicalHost: 'https://example.test',
                data: new SeoAuditInternalLinkData(
                    sourceUrl: $page->url,
                    targetReference: 'https://outside.example/path',
                ),
            );

        self::assertNull($external);

        self::assertSame(
            2,
            $run->crawlPages()->count(),
        );

        $summary =
            app(
                SeoAuditCrawlInventorySummaryQuery::class,
            )->get($run);

        self::assertSame(
            [
                'pages_total' => 2,
                'discovered_count' => 1,
                'fetched_count' => 1,
                'failed_count' => 0,
                'scanned_count' => 1,
                'links_total' => 1,
            ],
            $summary->toArray(),
        );
    }

    public function test_inventory_rejects_mutation_outside_running_run_and_invalid_observations(): void
    {
        $admin = Admin::factory()->create();

        $lifecycle =
            app(SeoAuditRunLifecycle::class);

        $run =
            $lifecycle->request($admin);

        $recorder =
            app(
                SeoAuditCrawlInventoryRecorder::class,
            );

        try {
            $recorder->recordPage(
                run: $run,
                canonicalHost: 'https://example.test',
                data: new SeoAuditCrawlPageData(
                    url: '/',
                ),
            );

            self::fail(
                'Requested run unexpectedly accepted crawl data.',
            );
        } catch (\DomainException) {
            self::assertTrue(true);
        }

        $run =
            $lifecycle->start($run);

        $this->expectException(
            \DomainException::class,
        );

        $recorder->recordPage(
            run: $run,
            canonicalHost: 'https://example.test',
            data: new SeoAuditCrawlPageData(
                url: '/',
                state: SeoAuditCrawlState::Fetched,
                httpStatus: 99,
            ),
        );
    }
}
