<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Seo\Sitemap\Data\SitemapIndexEntryData;
use App\Domain\Seo\Sitemap\Data\SitemapPartData;
use App\Domain\Seo\Sitemap\Data\SitemapUrlData;
use DateTimeInterface;

final readonly class SitemapXmlRenderer
{
    /**
     * @param  iterable<SitemapIndexEntryData>  $entries
     */
    public function renderIndex(
        iterable $entries,
        string $baseUrl,
    ): string {
        $baseUrl =
            rtrim(
                trim($baseUrl),
                '/',
            );

        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $entry) {
            $location =
                $baseUrl
                .'/'
                .ltrim(
                    $entry->filename,
                    '/',
                );

            $xml[] =
                '  <sitemap>';

            $xml[] =
                '    <loc>'
                .$this->escape(
                    $location,
                )
                .'</loc>';

            if (
                $entry->lastModified !==
                null
            ) {
                $xml[] =
                    '    <lastmod>'
                    .$this->escape(
                        $entry
                            ->lastModified
                            ->format(
                                DateTimeInterface::ATOM,
                            ),
                    )
                    .'</lastmod>';
            }

            $xml[] =
                '  </sitemap>';
        }

        $xml[] =
            '</sitemapindex>';

        return implode(
            "\n",
            $xml,
        )."\n";
    }

    public function renderPart(
        SitemapPartData $part,
    ): string {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach (
            $part->urls as $url
        ) {
            $xml[] =
                $this->renderUrl(
                    $url,
                );
        }

        $xml[] =
            '</urlset>';

        return implode(
            "\n",
            $xml,
        )."\n";
    }

    private function renderUrl(
        SitemapUrlData $url,
    ): string {
        $lines = [
            '  <url>',
            '    <loc>'
            .$this->escape(
                $url->url,
            )
            .'</loc>',
        ];

        if (
            $url->lastModified !==
            null
        ) {
            $lines[] =
                '    <lastmod>'
                .$this->escape(
                    $url
                        ->lastModified
                        ->format(
                            DateTimeInterface::ATOM,
                        ),
                )
                .'</lastmod>';
        }

        $lines[] =
            '  </url>';

        return implode(
            "\n",
            $lines,
        );
    }

    private function escape(
        string $value,
    ): string {
        return htmlspecialchars(
            $value,
            ENT_XML1
            | ENT_QUOTES,
            'UTF-8',
        );
    }
}
