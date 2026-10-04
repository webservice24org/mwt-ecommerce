<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\PromotionalBannerConfigSchema;
use PHPUnit\Framework\TestCase;

final class PromotionalBannerConfigSchemaTest extends TestCase
{
    public function test_valid_configuration_is_normalized(): void
    {
        $config = (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => '  Summer Sale  ',
            'description' => '  Save on selected products.  ',
            'image' => '  banners/summer-sale.jpg  ',
            'cta_label' => '  Shop Now  ',
            'cta_url' => '  /shop  ',
            'alignment' => 'center',
        ]);

        $this->assertSame(
            [
                'heading' => 'Summer Sale',
                'description' => 'Save on selected products.',
                'image' => 'banners/summer-sale.jpg',
                'cta_label' => 'Shop Now',
                'cta_url' => '/shop',
                'alignment' => 'center',
            ],
            $config,
        );
    }

    public function test_optional_values_may_be_empty(): void
    {
        $config = (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => 'Special Offer',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'left',
        ]);

        $this->assertSame(
            [
                'heading' => 'Special Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'left',
            ],
            $config,
        );
    }

    public function test_null_description_is_normalized_to_empty_string(): void
    {
        $config = (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => 'Special Offer',
            'description' => null,
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'center',
        ]);

        $this->assertSame(
            '',
            $config['description'],
        );
    }

    public function test_empty_optional_strings_are_normalized_to_null(): void
    {
        $config = (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => 'Offer',
            'description' => '',
            'image' => '   ',
            'cta_label' => '   ',
            'cta_url' => '   ',
            'alignment' => 'right',
        ]);

        $this->assertNull(
            $config['image'],
        );

        $this->assertNull(
            $config['cta_label'],
        );

        $this->assertNull(
            $config['cta_url'],
        );
    }

    public function test_heading_is_required(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => '',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'center',
        ]);
    }

    public function test_invalid_alignment_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => 'Offer',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'bottom',
        ]);
    }

    public function test_cta_label_and_url_must_be_provided_together(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => 'Offer',
            'description' => '',
            'image' => null,
            'cta_label' => 'Shop Now',
            'cta_url' => null,
            'alignment' => 'center',
        ]);
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        (
            new PromotionalBannerConfigSchema
        )->validate([
            'heading' => 'Offer',
            'description' => '',
            'image' => null,
            'cta_label' => null,
            'cta_url' => null,
            'alignment' => 'center',
            'unsupported' => true,
        ]);
    }
}
