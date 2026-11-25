<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\CanonicalUrlResolver;
use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use App\Domain\Seo\StructuredData\Nodes\OrganizationStructuredDataNode;
use App\Domain\Seo\StructuredData\Nodes\WebSiteStructuredDataNode;

final readonly class GlobalStructuredDataResolver
{
    public function __construct(
        private StructuredDataResolver $structuredData,
        private CanonicalUrlResolver $canonical,
    ) {}

    public function resolve(
        SeoSettingsData $settings,
    ): ?StructuredDataDocument {
        if (
            ! $settings->enableStructuredData
        ) {
            return null;
        }

        $siteUrl =
            $this->siteUrl(
                $settings,
            );

        if ($siteUrl === null) {
            return null;
        }

        $siteName =
            $this->firstNonBlank(
                $settings->siteName,
                $settings->organizationName,
            );

        $organizationName =
            $this->firstNonBlank(
                $settings->organizationName,
                $settings->siteName,
            );

        /** @var list<StructuredDataNode> $nodes */
        $nodes = [];

        $organizationId = null;

        if (
            $organizationName !==
            null
        ) {
            $organizationId =
                $this->entityId(
                    siteUrl: $siteUrl,

                    fragment: 'organization',
                );

            $nodes[] =
                new OrganizationStructuredDataNode(
                    id: $organizationId,

                    name: $organizationName,

                    url: $siteUrl,

                    logo: $this->logoUrl(
                        logo: $settings
                            ->organizationLogo,

                        canonicalHost: $settings
                            ->canonicalHost,
                    ),

                    sameAs: $this->socialProfiles(
                        $settings
                            ->organizationSocialProfiles,
                    ),
                );
        }

        if ($siteName !== null) {
            $nodes[] =
                new WebSiteStructuredDataNode(
                    id: $this->entityId(
                        siteUrl: $siteUrl,

                        fragment: 'website',
                    ),

                    name: $siteName,

                    url: $siteUrl,

                    publisherId: $organizationId,
                );
        }

        return $this
            ->structuredData
            ->resolve(
                settings: $settings,

                nodes: $nodes,
            );
    }

    private function siteUrl(
        SeoSettingsData $settings,
    ): ?string {
        $canonicalHost =
            $this->firstNonBlank(
                $settings->canonicalHost,
                $this->configuredAppUrl(),
            );

        if ($canonicalHost === null) {
            return null;
        }

        $resolved =
            $this
                ->canonical
                ->resolve(
                    canonicalOverride: null,

                    path: '/',

                    canonicalHost: $canonicalHost,
                );

        return $this->absoluteHttpUrl(
            $resolved,
        );
    }

    private function logoUrl(
        ?string $logo,
        ?string $canonicalHost,
    ): ?string {
        $logo =
            $this->trimNullable(
                $logo,
            );

        if ($logo === null) {
            return null;
        }

        if (
            $this->hasExplicitScheme(
                $logo,
            )
            && ! $this->isHttpScheme(
                $logo,
            )
        ) {
            return null;
        }

        $host =
            $this->firstNonBlank(
                $canonicalHost,
                $this->configuredAppUrl(),
            );

        if ($host === null) {
            return $this
                ->absoluteHttpUrl(
                    $logo,
                );
        }

        $resolved =
            $this
                ->canonical
                ->resolve(
                    canonicalOverride: $logo,

                    path: '/',

                    canonicalHost: $host,
                );

        return $this->absoluteHttpUrl(
            $resolved,
        );
    }

    /**
     * @param  array<int, string>  $profiles
     * @return list<string>
     */
    private function socialProfiles(
        array $profiles,
    ): array {
        $resolved = [];

        foreach ($profiles as $profile) {
            $profile =
                trim(
                    $profile,
                );

            if ($profile === '') {
                continue;
            }

            $profile =
                $this->absoluteHttpUrl(
                    $profile,
                );

            if ($profile === null) {
                continue;
            }

            $resolved[] =
                $profile;
        }

        return array_values(
            array_unique(
                $resolved,
            ),
        );
    }

    private function entityId(
        string $siteUrl,
        string $fragment,
    ): string {
        return rtrim(
            $siteUrl,
            '/',
        )
            .'/#'
            .$fragment;
    }

    private function absoluteHttpUrl(
        string $value,
    ): ?string {
        $value =
            trim(
                $value,
            );

        if (
            $value === ''
            || filter_var(
                $value,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return null;
        }

        $scheme =
            strtolower(
                (string) parse_url(
                    $value,
                    PHP_URL_SCHEME,
                ),
            );

        if (
            $scheme !== 'http'
            && $scheme !== 'https'
        ) {
            return null;
        }

        $fragmentPosition =
            strpos(
                $value,
                '#',
            );

        if (
            $fragmentPosition !==
            false
        ) {
            $value =
                substr(
                    $value,
                    0,
                    $fragmentPosition,
                );
        }

        return rtrim(
            $value,
            '/',
        );
    }

    private function hasExplicitScheme(
        string $value,
    ): bool {
        return preg_match(
            '/^[a-z][a-z0-9+.-]*:/i',
            $value,
        ) === 1;
    }

    private function isHttpScheme(
        string $value,
    ): bool {
        return preg_match(
            '/^https?:/i',
            $value,
        ) === 1;
    }

    private function configuredAppUrl(): ?string
    {
        $value =
            config(
                'app.url',
            );

        return is_string(
            $value,
        )
            ? $this->trimNullable(
                $value,
            )
            : null;
    }

    private function firstNonBlank(
        ?string ...$values,
    ): ?string {
        foreach ($values as $value) {
            $value =
                $this->trimNullable(
                    $value,
                );

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function trimNullable(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }
}
