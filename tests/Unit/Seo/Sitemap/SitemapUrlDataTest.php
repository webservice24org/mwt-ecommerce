<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Sitemap;

use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SitemapUrlDataTest extends TestCase
{
    public function test_it_accepts_valid_https_url(): void
    {
        $data =
            new SitemapUrlData(
                url: 'https://example.com/products/laptop',
            );

        $this->assertSame(
            'https://example.com/products/laptop',
            $data->url,
        );

        $this->assertNull(
            $data->lastModified,
        );
    }

    public function test_it_accepts_valid_http_url(): void
    {
        $data =
            new SitemapUrlData(
                url: 'http://example.com/products/laptop',
            );

        $this->assertSame(
            'http://example.com/products/laptop',
            $data->url,
        );
    }

    public function test_it_serializes_last_modified_as_iso_8601(): void
    {
        $lastModified =
            new DateTimeImmutable(
                '2026-11-17T12:30:00+06:00',
            );

        $data =
            new SitemapUrlData(
                url: 'https://example.com/products/laptop',

                lastModified: $lastModified,
            );

        $this->assertSame(
            [
                'url' => 'https://example.com/products/laptop',

                'last_modified' => '2026-11-17T12:30:00+06:00',
            ],
            $data->toArray(),
        );
    }

    public function test_it_rejects_empty_url(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapUrlData(
            url: '',
        );
    }

    public function test_it_rejects_relative_url(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapUrlData(
            url: '/products/laptop',
        );
    }

    public function test_it_rejects_javascript_scheme(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapUrlData(
            url: 'javascript:alert(1)',
        );
    }

    public function test_it_rejects_data_scheme(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapUrlData(
            url: 'data:text/html,test',
        );
    }

    public function test_it_rejects_ftp_scheme(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        new SitemapUrlData(
            url: 'ftp://example.com/file',
        );
    }
}
