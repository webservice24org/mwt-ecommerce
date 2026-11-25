<?php

declare(strict_types=1);

namespace App\Domain\Seo\Data;

final readonly class SeoSettingsData
{
    /**
     * @param  array<int, string>  $organizationSocialProfiles
     */
    public function __construct(
        public ?string $siteName,

        public string $titleSeparator,

        public string $defaultTitleTemplate,

        public ?string $defaultMetaDescription,

        public ?string $canonicalHost,

        public bool $defaultRobotsIndex,

        public bool $defaultRobotsFollow,

        public ?string $defaultOgImage,

        public string $twitterCard,

        public ?string $organizationName,

        public ?string $organizationLogo,

        public array $organizationSocialProfiles,

        public ?string $googleSiteVerification,

        public ?string $bingSiteVerification,

        public bool $enableStructuredData,

        public bool $enableOpenGraph,

        public bool $enableTwitterCards,

        public bool $enableSitemap,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'site_name' => $this->siteName,

            'title_separator' => $this->titleSeparator,

            'default_title_template' => $this->defaultTitleTemplate,

            'default_meta_description' => $this->defaultMetaDescription,

            'canonical_host' => $this->canonicalHost,

            'default_robots_index' => $this->defaultRobotsIndex,

            'default_robots_follow' => $this->defaultRobotsFollow,

            'default_og_image' => $this->defaultOgImage,

            'twitter_card' => $this->twitterCard,

            'organization_name' => $this->organizationName,

            'organization_logo' => $this->organizationLogo,

            'organization_social_profiles' => $this->organizationSocialProfiles,

            'google_site_verification' => $this->googleSiteVerification,

            'bing_site_verification' => $this->bingSiteVerification,

            'enable_structured_data' => $this->enableStructuredData,

            'enable_open_graph' => $this->enableOpenGraph,

            'enable_twitter_cards' => $this->enableTwitterCards,

            'enable_sitemap' => $this->enableSitemap,
        ];
    }
}
