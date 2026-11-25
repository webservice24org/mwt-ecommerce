<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class StorefrontSeoSocialMetaSourceContractTest extends TestCase
{
    public function test_storefront_seo_honors_authoritative_social_settings(): void
    {
        $path =
            dirname(
                __DIR__,
                3,
            )
            .'/resources/js/Components/Frontend/Seo/StorefrontSeo.tsx';

        self::assertFileExists(
            $path,
        );

        $source =
            file_get_contents(
                $path,
            );

        self::assertIsString(
            $source,
        );

        foreach (
            [
                'storefrontSocialSeo',
                'enable_open_graph',
                'enable_twitter_cards',
                'twitter_card',
                'og:site_name',
                'og:image:secure_url',
                'twitter:card',
                'twitter:title',
                'twitter:description',
                'twitter:image',
                'canonicalUrl',
                'structured-data',
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }
    }

    public function test_social_tags_are_guarded_by_their_global_toggles(): void
    {
        $path =
            dirname(
                __DIR__,
                3,
            )
            .'/resources/js/Components/Frontend/Seo/StorefrontSeo.tsx';

        $source =
            file_get_contents(
                $path,
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            '{openGraphEnabled && (',
            $source,
        );

        self::assertStringContainsString(
            '{twitterCardsEnabled && (',
            $source,
        );

        self::assertStringContainsString(
            "twitterImageUrl\n            ? configuredTwitterCard\n            : 'summary'",
            $source,
        );
    }
}
