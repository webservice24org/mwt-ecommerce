<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Services\SeoAuditBoundedCrawler;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditPageImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SeoAuditBoundedCrawlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_crawler_populates_bounded_page_link_redirect_and_image_observations(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://example.test/' => Http::response(
                <<<'HTML'
<html>
<head>
<title>Home</title>
<meta name="description" content="Store home">
<link rel="canonical" href="/">
</head>
<body>
<h1>Home</h1>
<p>Welcome to our useful online store.</p>
<a href="/products/widget">Widget</a>
<a href="/products/widget#details">Widget details</a>
<a href="/legacy">Legacy</a>
<a href="https://outside.example/page">External</a>
<a href="mailto:sales@example.test">Mail</a>
<img src="/storage/hero.jpg" alt="Store hero">
<img src="data:image/gif;base64,AAAA">
</body>
</html>
HTML,
                200,
                [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ],
            ),
            'https://example.test/products/widget' => Http::response(
                <<<'HTML'
<html>
<head>
<title>Widget</title>
<meta name="robots" content="noindex,follow">
<link rel="canonical" href="/products/widget">
</head>
<body>
<h1>Widget</h1>
<p>Widget details and product information.</p>
<img src="https://cdn.example.test/widget.jpg">
</body>
</html>
HTML,
                200,
                [
                    'Content-Type' => 'text/html',
                    'X-Robots-Tag' => 'all',
                ],
            ),
            'https://example.test/legacy' => Http::response(
                '',
                301,
                [
                    'Content-Type' => 'text/html',
                    'Location' => '/new-page',
                ],
            ),
            'https://example.test/new-page' => Http::response(
                '<html><head><title>New</title></head><body><h1>New page</h1><p>Destination page.</p></body></html>',
                200,
                [
                    'Content-Type' => 'text/html',
                ],
            ),
        ]);

        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request($admin),
        );

        $summary = app(
            SeoAuditBoundedCrawler::class,
        )->execute(
            run: $run,
            canonicalHost: 'https://example.test',
        );

        self::assertSame(
            SeoAuditRunStatus::Running,
            $run->refresh()->status,
        );

        self::assertSame(4, $summary->pagesTotal);
        self::assertSame(4, $summary->fetchedCount);
        self::assertSame(0, $summary->failedCount);
        self::assertSame(0, $summary->discoveredCount);
        self::assertSame(2, $summary->linksTotal);
        self::assertSame(2, $summary->imagesTotal);
        self::assertSame(4, $summary->fetchesAttempted);

        $home = SeoAuditCrawlPage::query()
            ->where('url', 'https://example.test/')
            ->firstOrFail();

        self::assertSame(
            SeoAuditCrawlState::Fetched,
            $home->state,
        );
        self::assertTrue($home->is_indexable);
        self::assertSame('Home', $home->title);
        self::assertSame(1, $home->h1_count);
        self::assertSame(
            'https://example.test/',
            $home->canonical_url,
        );

        $widget = SeoAuditCrawlPage::query()
            ->where(
                'url',
                'https://example.test/products/widget',
            )
            ->firstOrFail();

        self::assertFalse(
            $widget->is_indexable,
        );

        $legacy = SeoAuditCrawlPage::query()
            ->where(
                'url',
                'https://example.test/legacy',
            )
            ->firstOrFail();

        self::assertSame(301, $legacy->http_status);
        self::assertSame(
            'https://example.test/new-page',
            $legacy->redirect_url,
        );

        self::assertDatabaseHas(
            'seo_audit_internal_links',
            [
                'source_page_id' => $home->id,
                'target_page_id' => $widget->id,
                'occurrences' => 2,
            ],
        );

        self::assertDatabaseHas(
            'seo_audit_page_images',
            [
                'source_page_id' => $home->id,
                'image_url' => 'https://example.test/storage/hero.jpg',
                'alt_attribute_present' => true,
                'alt_text' => 'Store hero',
            ],
        );

        self::assertDatabaseHas(
            'seo_audit_page_images',
            [
                'source_page_id' => $widget->id,
                'image_url' => 'https://cdn.example.test/widget.jpg',
                'alt_attribute_present' => false,
            ],
        );

        self::assertSame(
            2,
            SeoAuditPageImage::query()->count(),
        );

        Http::assertNotSent(
            static fn (Request $request): bool => str_contains(
                $request->url(),
                'outside.example',
            ),
        );
    }

    public function test_connection_and_body_limit_failures_are_recorded_without_creating_findings(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://example.test/' => Http::response(
                str_repeat('x', 64),
                200,
                [
                    'Content-Type' => 'text/html',
                    'Content-Length' => (string) (
                        2000000 + 1
                    ),
                ],
            ),
        ]);

        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request($admin),
        );

        $summary = app(
            SeoAuditBoundedCrawler::class,
        )->execute(
            run: $run,
            canonicalHost: 'https://example.test',
        );

        self::assertSame(1, $summary->failedCount);
        self::assertSame(0, $run->findings()->count());

        $page = $run
            ->crawlPages()
            ->firstOrFail();

        self::assertSame(
            'body_too_large',
            $page->fetch_error_code,
        );
    }
}
