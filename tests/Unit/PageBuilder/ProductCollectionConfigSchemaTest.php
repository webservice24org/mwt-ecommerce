<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\ProductCollectionConfigSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductCollectionConfigSchemaTest extends TestCase
{
    public function test_grid_configuration_is_valid(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Latest Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
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
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'featured',
            ],
            'show_price' => true,
            'show_rating' => false,
            'show_badges' => true,
            'columns' => 3,
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
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Product Slider',
            'limit' => 12,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
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

    public function test_missing_source_defaults_to_latest(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $validated =
            $schema->validate([
                'title' => 'Products',
                'limit' => 8,
                'show_price' => true,
                'show_rating' => true,
                'show_badges' => true,
                'columns' => 4,
            ]);

        $this->assertSame(
            [
                'type' => 'latest',
            ],
            $validated['source'],
        );
    }

    #[DataProvider('simpleSourceProvider')]
    public function test_simple_source_is_valid(
        string $sourceType,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $validated =
            $schema->validate([
                'title' => 'Products',
                'limit' => 8,
                'source' => [
                    'type' => $sourceType,
                ],
                'show_price' => true,
                'show_rating' => true,
                'show_badges' => true,
                'columns' => 4,
            ]);

        $this->assertSame(
            [
                'type' => $sourceType,
            ],
            $validated['source'],
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function simpleSourceProvider(): array
    {
        return [
            'latest' => [
                'latest',
            ],
            'featured' => [
                'featured',
            ],
        ];
    }

    public function test_category_source_is_valid(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Electronics',
            'limit' => 8,
            'source' => [
                'type' => 'category',
                'category_id' => 5,
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ];

        $this->assertSame(
            $config,
            $schema->validate(
                $config,
            ),
        );
    }

    public function test_manual_source_is_valid(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Our Picks',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [
                    7,
                    12,
                    19,
                ],
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => false,
            'columns' => 4,
        ];

        $this->assertSame(
            $config,
            $schema->validate(
                $config,
            ),
        );
    }

    public function test_title_is_trimmed(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $validated =
            $schema->validate([
                'title' => '  Latest Products  ',
                'limit' => 8,
                'source' => [
                    'type' => 'latest',
                ],
                'show_price' => true,
                'show_rating' => true,
                'show_badges' => true,
                'columns' => 4,
            ]);

        $this->assertSame(
            'Latest Products',
            $validated['title'],
        );
    }

    public function test_empty_title_is_rejected(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => '   ',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    public function test_title_longer_than_supported_boundary_is_rejected(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => str_repeat(
                'a',
                121,
            ),
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    #[DataProvider('invalidLimitProvider')]
    public function test_invalid_limit_is_rejected(
        mixed $limit,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => $limit,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidLimitProvider(): array
    {
        return [
            'zero' => [
                0,
            ],
            'too large' => [
                25,
            ],
            'string' => [
                '8',
            ],
        ];
    }

    #[DataProvider('invalidColumnsProvider')]
    public function test_invalid_columns_are_rejected(
        mixed $columns,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => $columns,
        ]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidColumnsProvider(): array
    {
        return [
            'zero' => [
                0,
            ],
            'too large' => [
                7,
            ],
            'string' => [
                '4',
            ],
        ];
    }

    #[DataProvider('displayFieldProvider')]
    public function test_display_field_must_be_boolean(
        string $field,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ];

        $config[$field] = 1;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate(
            $config,
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function displayFieldProvider(): array
    {
        return [
            'show price' => [
                'show_price',
            ],
            'show rating' => [
                'show_rating',
            ],
            'show badges' => [
                'show_badges',
            ],
        ];
    }

    public function test_unknown_configuration_field_is_rejected(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
            'unknown' => true,
        ]);
    }

    public function test_unknown_source_type_is_rejected(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'sale',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    #[DataProvider('simpleSourceProvider')]
    public function test_simple_source_rejects_extra_fields(
        string $sourceType,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => $sourceType,
                'category_id' => 5,
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    #[DataProvider('invalidCategoryIdProvider')]
    public function test_category_source_requires_positive_category_id(
        mixed $categoryId,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'category',
                'category_id' => $categoryId,
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidCategoryIdProvider(): array
    {
        return [
            'null' => [
                null,
            ],
            'zero' => [
                0,
            ],
            'negative' => [
                -1,
            ],
            'string' => [
                '5',
            ],
        ];
    }

    public function test_category_source_rejects_extra_fields(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'category',
                'category_id' => 5,
                'product_ids' => [1],
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    public function test_manual_source_requires_product_ids(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    public function test_manual_source_rejects_empty_product_ids(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [],
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    public function test_manual_source_rejects_duplicate_product_ids(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [
                    5,
                    5,
                ],
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    #[DataProvider('invalidProductIdProvider')]
    public function test_manual_source_requires_positive_integer_product_ids(
        mixed $productId,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [
                    $productId,
                ],
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidProductIdProvider(): array
    {
        return [
            'zero' => [
                0,
            ],
            'negative' => [
                -1,
            ],
            'string' => [
                '1',
            ],
        ];
    }

    public function test_manual_source_rejects_more_than_twenty_four_products(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 24,
            'source' => [
                'type' => 'manual',
                'product_ids' => range(
                    1,
                    25,
                ),
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    public function test_manual_source_rejects_extra_fields(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [1],
                'category_id' => 5,
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
        ]);
    }

    public function test_partial_carousel_configuration_is_rejected(): void
    {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
            'autoplay' => true,
        ]);
    }

    #[DataProvider('carouselEffectProvider')]
    public function test_carousel_effect_is_valid(
        string $effect,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $config = [
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
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

    #[DataProvider('invalidCarouselEffectProvider')]
    public function test_invalid_carousel_effect_is_rejected(
        string $effect,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => $effect,
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidCarouselEffectProvider(): array
    {
        return [
            'none' => [
                'none',
            ],
            'unknown' => [
                'zoom',
            ],
        ];
    }

    #[DataProvider('invalidAutoplayDelayProvider')]
    public function test_invalid_autoplay_delay_is_rejected(
        mixed $delay,
    ): void {
        $schema =
            new ProductCollectionConfigSchema;

        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $schema->validate([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
            'show_price' => true,
            'show_rating' => true,
            'show_badges' => true,
            'columns' => 4,
            'autoplay' => true,
            'autoplay_delay' => $delay,
            'show_arrows' => true,
            'show_dots' => true,
            'effect' => 'fade',
        ]);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidAutoplayDelayProvider(): array
    {
        return [
            'too small' => [
                999,
            ],
            'too large' => [
                30001,
            ],
            'string' => [
                '5000',
            ],
        ];
    }
}
