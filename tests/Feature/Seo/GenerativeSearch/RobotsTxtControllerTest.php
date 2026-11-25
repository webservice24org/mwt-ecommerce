<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\GenerativeSearch;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RobotsTxtControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_robots_endpoint_returns_generic_policy_and_sitemap(): void
    {
        $this->saveSettings(
            enableSitemap: true,

            canonicalHost: 'https://example.com',
        );

        $response =
            $this->get(
                '/robots.txt',
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/plain; charset=UTF-8',
            );

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
            "Disallow: /cart\n",
            $content,
        );

        self::assertStringContainsString(
            "Disallow: /checkout\n",
            $content,
        );

        self::assertStringContainsString(
            "Sitemap: https://example.com/sitemap.xml\n",
            $content,
        );
    }

    public function test_sitemap_line_is_omitted_when_sitemap_is_disabled(): void
    {
        $this->saveSettings(
            enableSitemap: false,

            canonicalHost: 'https://example.com',
        );

        $content =
            $this
                ->get(
                    '/robots.txt',
                )
                ->assertOk()
                ->getContent();

        self::assertIsString(
            $content,
        );

        self::assertStringNotContainsString(
            'Sitemap:',
            $content,
        );
    }

    private function saveSettings(
        bool $enableSitemap,
        ?string $canonicalHost,
    ): void {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default store description.',

                canonicalHost: $canonicalHost,

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: $enableSitemap,
            ),
        );
    }
}
