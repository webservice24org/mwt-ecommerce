<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Data\SitemapPartData;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SitemapPartDataTest extends TestCase
{
    public function test_it_generates_deterministic_filename(): void
    {
        $part =
            new SitemapPartData(
                type: SitemapType::Products,

                partNumber: 3,

                urls: [
                    new SitemapUrlData(
                        url: 'https://example.com/products/test',
                    ),
                ],
            );

        self::assertSame(
            'products-sitemap-3.xml',
            $part->filename(),
        );
    }

    public function test_it_reports_url_count(): void
    {
        $part =
            new SitemapPartData(
                type: SitemapType::Products,

                partNumber: 1,

                urls: [
                    new SitemapUrlData(
                        'https://example.com/products/a',
                    ),

                    new SitemapUrlData(
                        'https://example.com/products/b',
                    ),
                ],
            );

        self::assertSame(
            2,
            $part->count(),
        );
    }

    public function test_latest_url_lastmod_becomes_part_lastmod(): void
    {
        $part =
            new SitemapPartData(
                type: SitemapType::Pages,

                partNumber: 1,

                urls: [
                    new SitemapUrlData(
                        url: 'https://example.com/pages/a',

                        lastModified: new DateTimeImmutable(
                            '2026-01-01T00:00:00+00:00',
                        ),
                    ),

                    new SitemapUrlData(
                        url: 'https://example.com/pages/b',

                        lastModified: new DateTimeImmutable(
                            '2026-05-01T00:00:00+00:00',
                        ),
                    ),
                ],
            );

        self::assertSame(
            '2026-05-01T00:00:00+00:00',
            $part
                ->lastModified()
                ?->format(
                    DATE_ATOM,
                ),
        );
    }

    public function test_empty_part_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapPartData(
            SitemapType::Products,
            1,
            [],
        );
    }

    public function test_invalid_part_number_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapPartData(
            SitemapType::Products,
            0,
            [
                new SitemapUrlData(
                    'https://example.com/products/test',
                ),
            ],
        );
    }
}
