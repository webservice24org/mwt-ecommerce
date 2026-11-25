<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Domain\Seo\Services\StorefrontIndexationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TechnicalSeoRobotsSitemapRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_endpoint_is_public_and_declares_crawler_policy_and_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response
            ->assertOk()
            ->assertSee('User-agent:', false)
            ->assertSee('Sitemap:', false);
    }

    public function test_sitemap_index_is_public_server_generated_xml(): void
    {
        $response = $this->get('/sitemap.xml');

        $response
            ->assertOk()
            ->assertSee('<?xml', false)
            ->assertSee('<sitemapindex', false)
            ->assertSee('</sitemapindex>', false);
    }

    public function test_search_and_sort_variants_fail_closed_for_listing_indexation(): void
    {
        $policy = app(StorefrontIndexationPolicy::class);

        self::assertTrue(
            $policy->allowsListingIndexing([]),
            'A clean storefront listing should remain eligible for indexing.',
        );

        self::assertFalse(
            $policy->allowsListingIndexing([
                'q' => 'usb cable',
            ]),
            'Internal search-result URLs must not become indexable listing variants.',
        );

        self::assertFalse(
            $policy->allowsListingIndexing([
                'sort' => 'price_asc',
            ]),
            'Sorted listing variants must not become independent indexable pages.',
        );
    }
}
