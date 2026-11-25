<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\FinalClosure;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiFinalPublicReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function robots_and_sitemap_remain_public_server_generated_release_surfaces(): void
    {
        $robots = $this->get('/robots.txt');

        $robots
            ->assertOk()
            ->assertSee('User-agent:', false)
            ->assertSee('Sitemap:', false);

        $sitemap = $this->get('/sitemap.xml');

        $sitemap
            ->assertOk()
            ->assertSee('<?xml', false)
            ->assertSee('<sitemapindex', false)
            ->assertSee('</sitemapindex>', false);

        $contentType = (string) $sitemap->headers->get('content-type');

        self::assertStringContainsString(
            'xml',
            strtolower($contentType),
        );
    }

    #[Test]
    public function final_release_keeps_primary_storefront_routes_registered(): void
    {
        foreach ([
            'products.index',
            'products.show',
            'categories.show',
            'frontend.pages.show',
        ] as $routeName) {
            self::assertTrue(
                app('router')->has($routeName),
                "Expected storefront route [{$routeName}] to remain registered.",
            );
        }
    }
}
