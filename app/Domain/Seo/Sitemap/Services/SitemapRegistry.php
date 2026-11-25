<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Services;

use App\Domain\Seo\Sitemap\Contracts\SitemapSource;
use App\Domain\Seo\Sitemap\Enums\SitemapType;
use InvalidArgumentException;

final class SitemapRegistry
{
    /**
     * @var array<string, SitemapSource>
     */
    private array $sources = [];

    public function register(
        SitemapSource $source,
    ): void {
        $key =
            $source
                ->type()
                ->value;

        if (
            isset(
                $this->sources[
                    $key
                ],
            )
        ) {
            throw new InvalidArgumentException(
                "Sitemap source [{$key}] is already registered.",
            );
        }

        $this->sources[
            $key
        ] = $source;
    }

    public function has(
        SitemapType $type,
    ): bool {
        return isset(
            $this->sources[
                $type->value
            ],
        );
    }

    public function get(
        SitemapType $type,
    ): SitemapSource {
        $source =
            $this->sources[
                $type->value
            ] ?? null;

        if (
            $source === null
        ) {
            throw new InvalidArgumentException(
                "Sitemap source [{$type->value}] is not registered.",
            );
        }

        return $source;
    }

    /**
     * @return array<string, SitemapSource>
     */
    public function all(): array
    {
        return $this->sources;
    }

    /**
     * @return list<SitemapType>
     */
    public function types(): array
    {
        return array_map(
            static fn (
                SitemapSource $source,
            ): SitemapType => $source->type(),

            array_values(
                $this->sources,
            ),
        );
    }
}
