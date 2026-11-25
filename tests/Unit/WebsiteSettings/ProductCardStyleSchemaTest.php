<?php

declare(strict_types=1);

namespace Tests\Unit\WebsiteSettings;

use App\Domain\WebsiteSettings\ProductCardStyleSchema;
use PHPUnit\Framework\TestCase;

final class ProductCardStyleSchemaTest extends TestCase
{
    public function test_all_supported_fields_normalize_and_defaults_are_empty(): void
    {
        $schema = new ProductCardStyleSchema;
        $errors = [];
        $config = array_fill_keys(ProductCardStyleSchema::COLORS, '#ABCDEF');
        $config['add_border_radius'] = 0;
        $config['order_border_radius'] = 32;

        $expected = array_fill_keys(ProductCardStyleSchema::COLORS, '#abcdef');
        $expected['add_border_radius'] = 0;
        $expected['order_border_radius'] = 32;
        self::assertSame($expected, $schema->normalize($config, $errors));
        self::assertSame([], $errors);
        self::assertSame([], $schema->normalize([], $errors));
    }

    public function test_title_hover_options_are_independent_and_strictly_validated(): void
    {
        $schema = new ProductCardStyleSchema;
        $errors = [];

        self::assertSame(
            ['title_hover_color' => '#abcdef', 'title_hover_decoration' => 'none'],
            $schema->normalize(['title_hover_color' => '#ABCDEF', 'title_hover_decoration' => 'none'], $errors),
        );
        self::assertSame(['title_hover_decoration' => 'underline'], $schema->normalize(['title_hover_decoration' => 'underline'], $errors));
        self::assertSame([], $errors);

        foreach (['overline', 'none; color:red', true, ['underline']] as $invalid) {
            $errors = [];
            self::assertSame([], $schema->normalize(['title_hover_decoration' => $invalid, 'title_hover_color' => 'red'], $errors));
            self::assertArrayHasKey('product_cards.title_hover_decoration', $errors);
            self::assertArrayHasKey('product_cards.title_hover_color', $errors);
        }
    }

    public function test_invalid_css_unknown_keys_and_out_of_range_radii_are_rejected(): void
    {
        $schema = new ProductCardStyleSchema;
        $errors = [];
        $config = $schema->normalize([
            'title_color' => '#123456',
            'background_color' => 'url(https://example.com)',
            'add_text_color' => '#fff;position:fixed',
            'order_border_radius' => 33,
            'add_border_radius' => '8',
            'position' => 'fixed',
            'offer_price_color' => ['red'],
        ], $errors);

        self::assertSame(['title_color' => '#123456'], $config);
        self::assertCount(6, $errors);
        self::assertArrayHasKey('product_cards.background_color', $errors);
        $errors = [];
        self::assertSame([], $schema->normalize(null, $errors));
        self::assertArrayHasKey('product_cards', $errors);
    }
}
