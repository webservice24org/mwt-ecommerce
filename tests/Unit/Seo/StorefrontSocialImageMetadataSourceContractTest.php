<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class StorefrontSocialImageMetadataSourceContractTest extends TestCase
{
    private function seoSource(): string
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

    public function test_open_graph_image_metadata_is_supported(): void
    {
        $source =
            $this->seoSource();

        foreach (
            [
                'imageMetadata',
                'og:image:alt',
                'og:image:width',
                'og:image:height',
                'twitter:image:alt',
                'ogImageMatchesMetadata',
                'twitterImageMatchesMetadata',
            ] as $needle
        ) {
            self::assertStringContainsString(
                $needle,
                $source,
            );
        }
    }

    public function test_alt_text_is_not_fabricated_when_stored_metadata_is_blank(): void
    {
        $source =
            $this->seoSource();

        self::assertStringContainsString(
            "imageMetadata\n            ?.alt\n            ?.trim()\n        || null",
            $source,
        );

        self::assertStringNotContainsString(
            '`${title}',
            $source,
        );

        self::assertStringNotContainsString(
            '`${ogTitle}',
            $source,
        );
    }

    public function test_metadata_is_emitted_only_when_the_final_image_url_matches(): void
    {
        $source =
            $this->seoSource();

        self::assertStringContainsString(
            'ogImageUrl === metadataImageUrl',
            $source,
        );

        self::assertStringContainsString(
            'twitterImageUrl === metadataImageUrl',
            $source,
        );
    }
}
