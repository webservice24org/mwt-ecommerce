<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Data\SitemapIndexEntryData;
use App\Domain\Seo\Sitemap\Data\SitemapPartData;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use App\Domain\Seo\Sitemap\Services\SitemapXmlRenderer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SitemapXmlRendererTest extends TestCase
{
    public function test_it_renders_sitemap_index(): void
    {
        $renderer =
            new SitemapXmlRenderer;

        $xml =
            $renderer->renderIndex(
                entries: [
                    new SitemapIndexEntryData(
                        type: SitemapType::Products,

                        partNumber: 1,

                        filename: 'products-sitemap-1.xml',

                        lastModified: new DateTimeImmutable(
                            '2026-11-17T10:00:00+00:00',
                        ),
                    ),
                ],

                baseUrl: 'https://example.com',
            );

        self::assertStringContainsString(
            '<?xml version="1.0" encoding="UTF-8"?>',
            $xml,
        );

        self::assertStringContainsString(
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $xml,
        );

        self::assertStringContainsString(
            '<loc>https://example.com/products-sitemap-1.xml</loc>',
            $xml,
        );

        self::assertStringContainsString(
            '<lastmod>2026-11-17T10:00:00+00:00</lastmod>',
            $xml,
        );
    }

    public function test_it_renders_urlset(): void
    {
        $renderer =
            new SitemapXmlRenderer;

        $part =
            new SitemapPartData(
                type: SitemapType::Products,

                partNumber: 1,

                urls: [
                    new SitemapUrlData(
                        url: 'https://example.com/products/test',

                        lastModified: new DateTimeImmutable(
                            '2026-11-17T12:00:00+00:00',
                        ),
                    ),
                ],
            );

        $xml =
            $renderer->renderPart(
                $part,
            );

        self::assertStringContainsString(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $xml,
        );

        self::assertStringContainsString(
            '<loc>https://example.com/products/test</loc>',
            $xml,
        );

        self::assertStringContainsString(
            '<lastmod>2026-11-17T12:00:00+00:00</lastmod>',
            $xml,
        );
    }

    public function test_xml_special_characters_are_escaped(): void
    {
        $renderer =
            new SitemapXmlRenderer;

        $part =
            new SitemapPartData(
                SitemapType::Products,
                1,
                [
                    new SitemapUrlData(
                        'https://example.com/products/test?a=1&b=2',
                    ),
                ],
            );

        $xml =
            $renderer->renderPart(
                $part,
            );

        self::assertStringContainsString(
            'https://example.com/products/test?a=1&amp;b=2',
            $xml,
        );

        self::assertStringNotContainsString(
            '?a=1&b=2',
            $xml,
        );
    }

    public function test_lastmod_is_omitted_when_missing(): void
    {
        $renderer =
            new SitemapXmlRenderer;

        $part =
            new SitemapPartData(
                SitemapType::Pages,
                1,
                [
                    new SitemapUrlData(
                        'https://example.com/pages/about',
                    ),
                ],
            );

        $xml =
            $renderer->renderPart(
                $part,
            );

        self::assertStringNotContainsString(
            '<lastmod>',
            $xml,
        );
    }
}
