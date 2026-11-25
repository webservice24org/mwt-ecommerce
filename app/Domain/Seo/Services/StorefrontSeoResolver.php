<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\ResolvedSeoData;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

final readonly class StorefrontSeoResolver
{
    public function __construct(
        private SeoEntitySourceFactory $sourceFactory,
        private AuthoritativeSeoResolver $resolver,
    ) {}

    public function resolve(
        Product|Category|Brand|Page $entity,
        SeoSettingsData $settings,
    ): ResolvedSeoData {
        return $this
            ->resolver
            ->resolve(
                source: $this
                    ->sourceFactory
                    ->from(
                        $entity,
                    ),

                settings: $settings,
            );
    }
}
