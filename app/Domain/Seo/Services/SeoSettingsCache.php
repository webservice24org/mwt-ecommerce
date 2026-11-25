<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class SeoSettingsCache
{
    public const KEY = 'seo.settings.global.v1';

    public function __construct(
        private CacheRepository $cache,
    ) {}

    /**
     * @param  callable(): SeoSettingsData  $resolver
     */
    public function remember(
        callable $resolver,
    ): SeoSettingsData {
        $cached =
            $this
                ->cache
                ->get(
                    self::KEY,
                );

        if (
            $cached instanceof SeoSettingsData
        ) {
            return $cached;
        }

        /*
         * Any stale or incorrectly typed cached value
         * must never escape into the SEO domain.
         */
        if (
            $cached !== null
        ) {
            $this
                ->cache
                ->forget(
                    self::KEY,
                );
        }

        $settings =
            $resolver();

        $this
            ->cache
            ->forever(
                self::KEY,
                $settings,
            );

        return $settings;
    }

    public function forget(): void
    {
        $this
            ->cache
            ->forget(
                self::KEY,
            );
    }
}
