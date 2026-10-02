<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\ProductCategoriesConfigSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductCategoriesConfigSchemaTest extends TestCase
{
    public function test_grid_configuration_is_valid(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $config = [
            'title' => 'Shop by Category',
            'category_ids' => [1, 2, 3],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
        ];

        $this->assertSame(
            $config,
            $schema->validate(
                $config,
            ),
        );
    }

    public function test_cards_configuration_is_valid(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $config = [
            'title' => 'Categories',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 3,
            'show_product_count' => true,
        ];

        $this->assertSame(
            $config,
            $schema->validate(
                $config,
            ),
        );
    }

    public function test_carousel_configuration_is_valid(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $config = [
            'title' => 'Browse Categories',
            'category_ids' => [10, 20],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'fade',
        ];

        $this->assertSame(
            $config,
            $schema->validate(
                $config,
            ),
        );
    }

    #[DataProvider('carouselEffectProvider')]
    public function test_carousel_effect_is_valid(
        string $effect,
    ): void {
        $schema =
            new ProductCategoriesConfigSchema;

        $config = [
            'title' => 'Browse Categories',
            'category_ids' => [],
            'show_name' => true,
            'columns' => 4,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => $effect,
        ];

        $this->assertSame(
            $config,
            $schema->validate(
                $config,
            ),
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function carouselEffectProvider(): array
    {
        return [
            'fade' => [
                'fade',
            ],
            'slide left' => [
                'slide_left',
            ],
            'slide right' => [
                'slide_right',
            ],
            'slide up' => [
                'slide_up',
            ],
            'slide down' => [
                'slide_down',
            ],
        ];
    }

    public function test_title_is_trimmed(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $validated = $schema->validate([
            'title' => '  Categories  ',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
        ]);

        $this->assertSame(
            'Categories',
            $validated['title'],
        );
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
            'unknown' => true,
        ]);
    }

    public function test_duplicate_category_ids_are_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [1, 1],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
        ]);
    }

    public function test_invalid_columns_are_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 8,
            'show_name' => true,
            'show_product_count' => false,
        ]);
    }

    public function test_non_boolean_display_settings_are_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => 1,
            'show_product_count' => false,
        ]);
    }

    public function test_partial_carousel_configuration_is_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
            'autoplay' => true,
        ]);
    }

    public function test_carousel_configuration_without_effect_is_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
        ]);
    }

    public function test_invalid_carousel_effect_is_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'zoom',
        ]);
    }

    public function test_none_carousel_effect_is_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'none',
        ]);
    }

    public function test_invalid_autoplay_delay_is_rejected(): void
    {
        $schema =
            new ProductCategoriesConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Categories',
            'category_ids' => [],
            'columns' => 4,
            'show_name' => true,
            'show_product_count' => false,
            'autoplay' => true,
            'autoplay_delay' => 500,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'fade',
        ]);
    }
}
