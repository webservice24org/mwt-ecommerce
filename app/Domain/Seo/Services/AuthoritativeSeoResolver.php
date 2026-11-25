<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\ResolvedSeoData;
use App\Domain\Seo\Data\SeoEntitySourceData;
use App\Domain\Seo\Data\SeoSettingsData;

final readonly class AuthoritativeSeoResolver
{
    public function __construct(
        private SeoTitleTemplateResolver $titleTemplateResolver,
    ) {}

    public function resolve(
        SeoEntitySourceData $source,
        SeoSettingsData $settings,
    ): ResolvedSeoData {
        $title =
            $this->firstString([
                $source->metaTitle,
                $source->pageTitle,
            ]) ??
            '';

        $templatedTitle =
            $this
                ->titleTemplateResolver
                ->resolve(
                    template: $settings->defaultTitleTemplate,

                    pageTitle: $title,

                    separator: $settings->titleSeparator,

                    siteName: $settings->siteName,
                );

        $description =
            $this->firstString([
                $source->metaDescription,
                $source->fallbackDescription,
                $settings->defaultMetaDescription,
            ]);

        $overrides =
            $source->overrides;

        $robotsIndex =
            $overrides->robotsIndex
            ?? $settings->defaultRobotsIndex;

        $robotsFollow =
            $overrides->robotsFollow
            ?? $settings->defaultRobotsFollow;

        $ogTitle =
            $this->firstString([
                $overrides->ogTitle,
                $templatedTitle,
            ]) ??
            $templatedTitle;

        $ogDescription =
            $this->firstString([
                $overrides->ogDescription,
                $description,
            ]);

        $ogImage =
            $this->firstString([
                $overrides->ogImage,
                $source->fallbackImage,
                $settings->defaultOgImage,
            ]);

        $twitterTitle =
            $this->firstString([
                $overrides->twitterTitle,
                $ogTitle,
            ]) ??
            $ogTitle;

        $twitterDescription =
            $this->firstString([
                $overrides->twitterDescription,
                $ogDescription,
            ]);

        $twitterImage =
            $this->firstString([
                $overrides->twitterImage,
                $ogImage,
            ]);

        return new ResolvedSeoData(
            title: $templatedTitle,

            description: $description,

            /*
             * 10.4D will turn this into the final canonical URL.
             */
            canonicalOverride: $this->nullableString(
                $overrides->canonicalUrl,
            ),

            /*
             * These are inheritance-resolved booleans.
             * 10.4D may still impose stronger indexation policy.
             */
            robotsIndex: $robotsIndex,

            robotsFollow: $robotsFollow,

            ogTitle: $ogTitle,

            ogDescription: $ogDescription,

            ogImage: $ogImage,

            twitterTitle: $twitterTitle,

            twitterDescription: $twitterDescription,

            twitterImage: $twitterImage,
        );
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function firstString(
        array $values,
    ): ?string {
        foreach (
            $values as $value
        ) {
            $value =
                $this->nullableString(
                    $value,
                );

            if (
                $value !== null
            ) {
                return $value;
            }
        }

        return null;
    }

    private function nullableString(
        ?string $value,
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value !== ''
            ? $value
            : null;
    }
}
