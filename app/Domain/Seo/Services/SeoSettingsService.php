<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Repositories\SeoSettingsRepository;
use App\Domain\Seo\Sitemap\Services\SitemapInvalidator;

final readonly class SeoSettingsService
{
    public function __construct(
        private SeoSettingsRepository $repository,
        private SeoSettingsCache $cache,
        private readonly SitemapInvalidator $sitemapInvalidator,
    ) {}

    public function get(): SeoSettingsData
    {
        return $this
            ->cache
            ->remember(
                fn (): SeoSettingsData => $this
                    ->repository
                    ->get(),
            );
    }

    public function save(
        SeoSettingsData $data,
    ): SeoSettingsData {
        $saved =
            $this
                ->repository
                ->save(
                    $data,
                );

        $this
            ->cache
            ->forget();
        $this
            ->sitemapInvalidator
            ->invalidate();

        return $saved;
    }

    public function refresh(): SeoSettingsData
    {
        $this
            ->cache
            ->forget();

        return $this->get();
    }

    public function forget(): void
    {
        $this
            ->cache
            ->forget();
    }
}
