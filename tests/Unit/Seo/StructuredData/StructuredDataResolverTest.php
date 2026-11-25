<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Contracts\StructuredDataNode;
use App\Domain\Seo\StructuredData\Services\StructuredDataResolver;
use PHPUnit\Framework\TestCase;

final class StructuredDataResolverTest extends TestCase
{
    public function test_disabled_structured_data_returns_null(): void
    {
        $resolver =
            new StructuredDataResolver;

        $result =
            $resolver->resolve(
                settings: $this->settings(
                    enabled: false,
                ),

                nodes: [
                    $this->node(),
                ],
            );

        self::assertNull(
            $result,
        );
    }

    public function test_empty_nodes_return_null(): void
    {
        $resolver =
            new StructuredDataResolver;

        self::assertNull(
            $resolver->resolve(
                settings: $this->settings(
                    enabled: true,
                ),

                nodes: [],
            ),
        );
    }

    public function test_enabled_structured_data_returns_document(): void
    {
        $resolver =
            new StructuredDataResolver;

        $document =
            $resolver->resolve(
                settings: $this->settings(
                    enabled: true,
                ),

                nodes: [
                    $this->node(),
                ],
            );

        self::assertNotNull(
            $document,
        );

        self::assertFalse(
            $document->isEmpty(),
        );

        self::assertSame(
            'WebSite',
            $document
                ->toArray()['@graph'][0]['@type'],
        );
    }

    private function node(): StructuredDataNode
    {
        return new class implements StructuredDataNode
        {
            public function toStructuredData(): array
            {
                return [
                    '@type' => 'WebSite',

                    'name' => 'MWT Store',
                ];
            }
        };
    }

    private function settings(
        bool $enabled,
    ): SeoSettingsData {
        return new SeoSettingsData(
            siteName: 'MWT Store',

            titleSeparator: '|',

            defaultTitleTemplate: '{page_title} {separator} {site_name}',

            defaultMetaDescription: null,

            canonicalHost: 'https://example.com',

            defaultRobotsIndex: true,

            defaultRobotsFollow: true,

            defaultOgImage: null,

            twitterCard: 'summary_large_image',

            organizationName: 'MWT Store',

            organizationLogo: null,

            organizationSocialProfiles: [],

            googleSiteVerification: null,

            bingSiteVerification: null,

            enableStructuredData: $enabled,

            enableOpenGraph: true,

            enableTwitterCards: true,

            enableSitemap: true,
        );
    }
}
