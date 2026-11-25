<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

final readonly class ResolvedSeoData
{
    public function __construct(
        public string $title,
        public ?string $description,

        public ?string $canonicalOverride,

        public bool $robotsIndex,
        public bool $robotsFollow,

        public string $ogTitle,
        public ?string $ogDescription,
        public ?string $ogImage,

        public string $twitterTitle,
        public ?string $twitterDescription,
        public ?string $twitterImage,

        /*
         * Final storefront canonical.
         *
         * This remains nullable because the authoritative
         * metadata resolver does not know the current
         * storefront route/path.
         *
         * Storefront controllers attach it after resolving
         * route-specific canonical policy.
         */
        public ?string $canonicalUrl = null,
    ) {}

    public function withCanonicalUrl(
        string $canonicalUrl,
    ): self {
        return new self(
            title: $this->title,

            description: $this->description,

            canonicalOverride: $this->canonicalOverride,

            robotsIndex: $this->robotsIndex,

            robotsFollow: $this->robotsFollow,

            ogTitle: $this->ogTitle,

            ogDescription: $this->ogDescription,

            ogImage: $this->ogImage,

            twitterTitle: $this->twitterTitle,

            twitterDescription: $this->twitterDescription,

            twitterImage: $this->twitterImage,

            canonicalUrl: $canonicalUrl,
        );
    }

    public function withRobotsIndex(
        bool $robotsIndex,
    ): self {
        return new self(
            title: $this->title,

            description: $this->description,

            canonicalOverride: $this->canonicalOverride,

            robotsIndex: $robotsIndex,

            robotsFollow: $this->robotsFollow,

            ogTitle: $this->ogTitle,

            ogDescription: $this->ogDescription,

            ogImage: $this->ogImage,

            twitterTitle: $this->twitterTitle,

            twitterDescription: $this->twitterDescription,

            twitterImage: $this->twitterImage,

            canonicalUrl: $this->canonicalUrl,
        );
    }

    /**
     * @return array{
     *     title: string,
     *     description: string|null,
     *     canonical_override: string|null,
     *     canonical_url: string|null,
     *     robots_index: bool,
     *     robots_follow: bool,
     *     og_title: string,
     *     og_description: string|null,
     *     og_image: string|null,
     *     twitter_title: string,
     *     twitter_description: string|null,
     *     twitter_image: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,

            'description' => $this->description,

            'canonical_override' => $this->canonicalOverride,

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
