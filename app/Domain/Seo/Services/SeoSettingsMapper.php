<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Models\SeoSetting;

final class SeoSettingsMapper
{
    public function fromModel(
        SeoSetting $setting,
    ): SeoSettingsData {
        $profiles =
            $setting
                ->organization_social_profiles;

        return new SeoSettingsData(
            siteName: $setting->site_name,

            titleSeparator: $setting->title_separator,

            defaultTitleTemplate: $setting->default_title_template,

            defaultMetaDescription: $setting->default_meta_description,

            canonicalHost: $setting->canonical_host,

            defaultRobotsIndex: $setting->default_robots_index,

            defaultRobotsFollow: $setting->default_robots_follow,

            defaultOgImage: $setting->default_og_image,

            twitterCard: $setting->twitter_card,

            organizationName: $setting->organization_name,

            organizationLogo: $setting->organization_logo,

            organizationSocialProfiles: is_array(
                $profiles,
            )
            ? array_values(
                array_filter(
                    array_map(
                        static fn (
                            string $value,
                        ): string => trim(
                            $value,
                        ),

                        $profiles,
                    ),
                    static fn (
                        string $value,
                    ): bool => $value !== '',
                ),
            )
            : [],

            googleSiteVerification: $setting->google_site_verification,

            bingSiteVerification: $setting->bing_site_verification,

            enableStructuredData: $setting->enable_structured_data,

            enableOpenGraph: $setting->enable_open_graph,

            enableTwitterCards: $setting->enable_twitter_cards,

            enableSitemap: $setting->enable_sitemap,
        );
    }
}
