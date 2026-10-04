<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\ContentConfigSchema;
use PHPUnit\Framework\TestCase;

final class ContentConfigSchemaTest extends TestCase
{
    public function test_valid_configuration_is_normalized(): void
    {
        $config = (
            new ContentConfigSchema
        )->validate([
            'heading' => '  About Our Store  ',
            'body' => '  We make shopping simple.  ',
            'image' => '  /storage/page-builder/about.webp  ',
            'image_alt' => '  Our team in the store  ',
            'alignment' => 'center',
        ]);

        $this->assertSame(
            [
                'heading' => 'About Our Store',
                'body' => 'We make shopping simple.',
                'image' => '/storage/page-builder/about.webp',
                'image_alt' => 'Our team in the store',
                'alignment' => 'center',
            ],
            $config,
        );
    }

    public function test_optional_values_may_be_empty_or_null(): void
    {
        $config = (
            new ContentConfigSchema
        )->validate([
            'heading' => null,
            'body' => 'Content body',
            'image' => '   ',
            'image_alt' => '',
            'alignment' => 'left',
        ]);

        $this->assertSame(
            [
                'heading' => '',
                'body' => 'Content body',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ],
            $config,
        );
    }

    public function test_body_is_required(): void
    {
        try {
            (
                new ContentConfigSchema
            )->validate([
                'heading' => 'Heading',
                'body' => '',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ]);

            $this->fail(
                'Expected empty content body to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'body',
                $exception->errors(),
            );
        }
    }

    public function test_body_may_not_exceed_5000_characters(): void
    {
        try {
            (
                new ContentConfigSchema
            )->validate([
                'heading' => '',
                'body' => str_repeat('a', 5001),
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ]);

            $this->fail(
                'Expected oversized body to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'body',
                $exception->errors(),
            );
        }
    }

    public function test_invalid_alignment_is_rejected(): void
    {
        try {
            (
                new ContentConfigSchema
            )->validate([
                'heading' => '',
                'body' => 'Content body',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'bottom',
            ]);

            $this->fail(
                'Expected invalid alignment to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'alignment',
                $exception->errors(),
            );
        }
    }

    public function test_image_alt_may_not_exceed_255_characters(): void
    {
        try {
            (
                new ContentConfigSchema
            )->validate([
                'heading' => '',
                'body' => 'Content body',
                'image' => '/image.webp',
                'image_alt' => str_repeat('a', 256),
                'alignment' => 'left',
            ]);

            $this->fail(
                'Expected oversized image alt text to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'image_alt',
                $exception->errors(),
            );
        }
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        try {
            (
                new ContentConfigSchema
            )->validate([
                'heading' => '',
                'body' => 'Content body',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
                'unsafe_html' => '<script>alert(1)</script>',
            ]);

            $this->fail(
                'Expected unsupported configuration field to be rejected.',
            );
        } catch (
            InvalidSectionConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'unsafe_html',
                $exception->errors(),
            );
        }
    }
}
