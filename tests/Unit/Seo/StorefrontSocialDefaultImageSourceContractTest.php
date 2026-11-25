<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class StorefrontSocialDefaultImageSourceContractTest extends TestCase
{
    private function source(): string
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

    public function test_generic_pages_fall_back_to_global_default_social_image(): void
    {
        $source =
            $this->source();

        self::assertStringContainsString(
            'props.image',
            $source,
        );

        self::assertStringContainsString(
            'default_og_image',
            $source,
        );

        self::assertStringContainsString(
            'const twitterImage',
            $source,
        );

        self::assertStringContainsString(
            ': ogImage',
            $source,
        );
    }

    public function test_entity_pages_keep_using_server_resolved_social_images(): void
    {
        $source =
            $this->source();

        self::assertStringContainsString(
            '? props.seo.og_image',
            $source,
        );

        self::assertStringContainsString(
            '? props.seo.twitter_image',
            $source,
        );
    }

    public function test_missing_global_image_does_not_force_a_fake_social_image(): void
    {
        $source =
            $this->source();

        self::assertStringContainsString(
            '?? null',
            $source,
        );

        self::assertStringContainsString(
            "twitterImageUrl\n            ? configuredTwitterCard\n            : 'summary'",
            $source,
        );
    }
}
