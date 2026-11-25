<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

final readonly class EntitySeoData
{
    public function __construct(
        public ?string $title,

        public ?string $description,

        public ?string $canonicalUrl,

        public ?bool $robotsIndex,

        public ?bool $robotsFollow,

        public ?string $ogTitle,

        public ?string $ogDescription,

        public ?string $ogImage,

        public ?string $twitterTitle,

        public ?string $twitterDescription,

        public ?string $twitterImage,
    ) {}

    public static function empty(): self
    {
        return new self(
            title: null,

            description: null,

            canonicalUrl: null,

            robotsIndex: null,

            robotsFollow: null,

            ogTitle: null,

            ogDescription: null,

            ogImage: null,

            twitterTitle: null,

            twitterDescription: null,

            twitterImage: null,
        );
    }

    public function hasOverrides(): bool
    {
        return
            $this->title !== null ||
            $this->description !== null ||
            $this->canonicalUrl !== null ||
            $this->robotsIndex !== null ||
            $this->robotsFollow !== null ||
            $this->ogTitle !== null ||
            $this->ogDescription !== null ||
            $this->ogImage !== null ||
            $this->twitterTitle !== null ||
            $this->twitterDescription !== null ||
            $this->twitterImage !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,

            'description' => $this->description,

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
