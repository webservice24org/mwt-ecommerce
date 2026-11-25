<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class OpenGraphSocialImageSeoClosureSourceContractTest extends TestCase
{
    private function storefrontSeoSource(): string
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

        return $source;
    }

    private function productPageSource(): string
    {
        $path =
            dirname(
                __DIR__,
                3,
            )
            .'/resources/js/Pages/Frontend/Products/Show.tsx';

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

        return $source;
    }

    public function test_open_graph_contract_is_complete_and_canonical_aligned(): void
    {
        $source =
            $this->storefrontSeoSource();

        foreach (
            [
                'og:title',
                'og:description',
                'og:type',
                'og:url',
                'og:site_name',
                'og:image',
                'og:image:secure_url',
                'og:image:alt',
                'og:image:width',
                'og:image:height',
                'canonicalUrl',
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }

        self::assertStringContainsString(
            'content={',
            $source,
        );

        self::assertStringContainsString(
            'canonicalUrl',
            $source,
        );
    }

    public function test_x_twitter_card_contract_is_complete(): void
    {
        $source =
            $this->storefrontSeoSource();

        foreach (
            [
                'twitter:card',
                'twitter:title',
                'twitter:description',
                'twitter:image',
                'twitter:image:alt',
                'configuredTwitterCard',
                'summary_large_image',
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }
    }

    public function test_global_social_toggles_guard_their_own_tag_groups(): void
    {
        $source =
            $this->storefrontSeoSource();

        self::assertStringContainsString(
            '{openGraphEnabled && (',
            $source,
        );

        self::assertStringContainsString(
            '{twitterCardsEnabled && (',
            $source,
        );
    }

    public function test_image_alt_and_dimensions_are_url_match_guarded(): void
    {
        $source =
            $this->storefrontSeoSource();

        foreach (
            [
                'ogImageMatchesMetadata',
                'twitterImageMatchesMetadata',
                'ogImageUrl === metadataImageUrl',
                'twitterImageUrl === metadataImageUrl',
                'positiveDimension',
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }

        self::assertStringContainsString(
            "?.alt\n            ?.trim()\n        || null",
            $source,
        );
    }

    public function test_product_page_uses_the_first_authoritative_image_only_as_metadata_candidate(): void
    {
        $source =
            $this->productPageSource();

        foreach (
            [
                'const preferredSocialImage',
                'product.images[0]',
                'imageMetadata={',
                'preferredSocialImage',
                'seo={',
                'structuredData',
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }
    }

    public function test_structured_data_rendering_remains_present_after_social_seo_changes(): void
    {
        $source =
            $this->storefrontSeoSource();

        self::assertStringContainsString(
            'type="application/ld+json"',
            $source,
        );

        self::assertStringContainsString(
            'JSON.stringify(',
            $source,
        );

        self::assertStringContainsString(
            'jsonLd',
            $source,
        );
    }
}
