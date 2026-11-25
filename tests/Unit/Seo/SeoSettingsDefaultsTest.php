<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Services\SeoSettingsDefaults;
use Tests\TestCase;

final class SeoSettingsDefaultsTest extends TestCase
{
    public function test_defaults_are_safe_for_indexable_storefront(): void
    {
        config([
            'app.name' => 'MWT Shop',

            'app.url' => 'https://shop.example.com',
        ]);

        $settings =
            app(
                SeoSettingsDefaults::class,
            )->make();

        $this->assertSame(
            'MWT Shop',
            $settings->siteName,
        );

        $this->assertSame(
            '|',
            $settings->titleSeparator,
        );

        $this->assertSame(
            '{page_title} {separator} {site_name}',
            $settings->defaultTitleTemplate,
        );

        $this->assertSame(
            'https://shop.example.com',
            $settings->canonicalHost,
        );

        $this->assertTrue(
            $settings->defaultRobotsIndex,
        );

        $this->assertTrue(
            $settings->defaultRobotsFollow,
        );

        $this->assertTrue(
            $settings->enableStructuredData,
        );

        $this->assertTrue(
            $settings->enableOpenGraph,
        );

        $this->assertTrue(
            $settings->enableTwitterCards,
        );

        $this->assertTrue(
            $settings->enableSitemap,
        );
    }
}
