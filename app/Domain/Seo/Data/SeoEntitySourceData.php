<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

final readonly class SeoEntitySourceData
{
    public function __construct(
        public string $pageTitle,

        public ?string $metaTitle,
        public ?string $metaDescription,

        public ?string $fallbackDescription,
        public ?string $fallbackImage,

        public EntitySeoData $overrides,
    ) {}
}
