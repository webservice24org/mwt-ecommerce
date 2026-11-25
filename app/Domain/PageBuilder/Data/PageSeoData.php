<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Data;

final readonly class PageSeoData
{
    public function __construct(
        public ?string $metaTitle,
        public ?string $metaDescription,

        public ?string $canonicalUrl = null,
        public ?bool $robotsIndex = null,
        public ?bool $robotsFollow = null,

        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,

        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $twitterImage = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'meta_title' => $this->metaTitle,

            'meta_description' => $this->metaDescription,

            'canonical_url' => $this->canonicalUrl,

            'robots_index' => $this->robotsIndex,

            'robots_follow' => $this->robotsFollow,

            'og_title' => $this->ogTitle,

            'og_description' => $this->ogDescription,

            'og_image' => $this->ogImage,

            'twitter_title' => $this->twitterTitle,

            'twitter_description' => $this->twitterDescription,

            'twitter_image' => $this->twitterImage,
        ];
    }
}
