<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\StructuredData;

use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\StructuredData\Data\BreadcrumbStructuredDataItemData;
use App\Domain\Seo\StructuredData\Services\BreadcrumbStructuredDataResolver;
use Tests\TestCase;

final class BreadcrumbStructuredDataResolverTest extends TestCase
{
    public function test_it_resolves_internal_paths_to_absolute_urls(): void
    {
        $document =
            app(
                BreadcrumbStructuredDataResolver::class,
            )->resolve(
                settings: $this->settings(),

                items: [
                    new BreadcrumbStructuredDataItemData(
                        'Home',
                        '/',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        'Shop',
                        '/products',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        'USB Charger',
                    ),
                ],
            );

        self::assertNotNull(
            $document,
        );

        $items =
            $document
                ->toArray()['@graph'][0]['itemListElement'];

        self::assertSame(
            'https://example.com',
            $items[0]['item'],
        );

        self::assertSame(
            'https://example.com/products',
            $items[1]['item'],
        );

        self::assertArrayNotHasKey(
            'item',
            $items[2],
        );
    }

    public function test_invalid_external_like_path_is_not_exposed(): void
    {
        $document =
            app(
                BreadcrumbStructuredDataResolver::class,
            )->resolve(
                settings: $this->settings(),

                items: [
                    new BreadcrumbStructuredDataItemData(
                        'Home',
                        '/',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        'Unsafe',
                        '//evil.example.com/test',
                    ),
                ],
            );

        self::assertNotNull(
            $document,
        );

        self::assertArrayNotHasKey(
            'item',
            $document
                ->toArray()['@graph'][0]['itemListElement'][1],
        );
    }

    public function test_blank_items_are_removed(): void
    {
        $document =
            app(
                BreadcrumbStructuredDataResolver::class,
            )->resolve(
                settings: $this->settings(),

                items: [
                    new BreadcrumbStructuredDataItemData(
                        'Home',
                        '/',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        '   ',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        'Product',
                    ),
                ],
            );

        self::assertNotNull(
            $document,
        );

        self::assertCount(
            2,
            $document
                ->toArray()['@graph'][0]['itemListElement'],
        );
    }

    public function test_less_than_two_valid_items_returns_null(): void
    {
        $document =
            app(
                BreadcrumbStructuredDataResolver::class,
            )->resolve(
                settings: $this->settings(),

                items: [
                    new BreadcrumbStructuredDataItemData(
                        'Home',
                        '/',
                    ),
                ],
            );

        self::assertNull(
            $document,
        );
    }

    public function test_global_structured_data_disable_returns_null(): void
    {
        $document =
            app(
                BreadcrumbStructuredDataResolver::class,
            )->resolve(
                settings: $this->settings(
                    enabled: false,
                ),

                items: [
                    new BreadcrumbStructuredDataItemData(
                        'Home',
                        '/',
                    ),

                    new BreadcrumbStructuredDataItemData(
                        'Shop',
                    ),
                ],
            );

        self::assertNull(
            $document,
        );
    }

    private function settings(
        bool $enabled = true,
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
