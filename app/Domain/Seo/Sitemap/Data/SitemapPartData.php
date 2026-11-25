<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Data;

use App\Domain\Seo\Sitemap\Enums\SitemapType;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class SitemapPartData
{
    /**
     * @param  list<SitemapUrlData>  $urls
     */
    public function __construct(
        public SitemapType $type,
        public int $partNumber,
        public array $urls,
    ) {
        if ($this->partNumber < 1) {
            throw new InvalidArgumentException(
                'Sitemap part number must be greater than zero.',
            );
        }

        if ($this->urls === []) {
            throw new InvalidArgumentException(
                'A sitemap part must contain at least one URL.',
            );
        }
    }

    public function filename(): string
    {
        return sprintf(
            '%s-sitemap-%d.xml',
            $this->type->value,
            $this->partNumber,
        );
    }

    public function lastModified(): ?DateTimeImmutable
    {
        $latest = null;

        foreach ($this->urls as $url) {
            if ($url->lastModified === null) {
                continue;
            }

            if (
                $latest === null
                || $url->lastModified > $latest
            ) {
                $latest =
                    $url->lastModified;
            }
        }

        return $latest;
    }

    public function count(): int
    {
        return count(
            $this->urls,
        );
    }
}
