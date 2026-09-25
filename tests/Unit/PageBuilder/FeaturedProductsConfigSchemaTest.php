<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Sections\Schemas\FeaturedProductsConfigSchema;
use PHPUnit\Framework\TestCase;

final class FeaturedProductsConfigSchemaTest extends TestCase
{
    public function test_valid_configuration_is_normalized(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        $config = $schema->validate([
            'title' => '  Featured Products  ',
            'limit' => 8,
        ]);

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $config,
        );

    }

    public function test_title_is_required(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        try {
            $schema->validate([
                'limit' => 8,
            ]);

            $this->fail(
                'Expected invalid section configuration.',
            );
        } catch (InvalidSectionConfiguration $exception) {
            $this->assertArrayHasKey(
                'title',
                $exception->errors(),
            );
        }
    }

    public function test_whitespace_only_title_is_rejected(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        try {
            $schema->validate([
                'title' => '   ',
                'limit' => 8,
            ]);

            $this->fail(
                'Expected invalid section configuration.',
            );
        } catch (InvalidSectionConfiguration $exception) {
            $this->assertArrayHasKey(
                'title',
                $exception->errors(),
            );
        }
    }

    public function test_limit_must_be_within_supported_boundary(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        foreach ([0, 25] as $limit) {
            try {
                $schema->validate([
                    'title' => 'Featured Products',
                    'limit' => $limit,
                ]);

                $this->fail(
                    sprintf(
                        'Expected limit [%d] to be rejected.',
                        $limit,
                    ),
                );
            } catch (InvalidSectionConfiguration $exception) {
                $this->assertArrayHasKey(
                    'limit',
                    $exception->errors(),
                );
            }
        }
    }

    public function test_unknown_configuration_fields_are_rejected(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        try {
            $schema->validate([
                'title' => 'Featured Products',
                'limit' => 8,
                'sql' => 'select * from products',
            ]);

            $this->fail(
                'Expected unknown configuration field to be rejected.',
            );
        } catch (InvalidSectionConfiguration $exception) {
            $this->assertArrayHasKey(
                'sql',
                $exception->errors(),
            );
        }
    }

    public function test_it_defaults_missing_source_to_featured(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        $config = $schema->validate([
            'title' => 'Featured Products',
            'limit' => 8,
        ]);

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $config,
        );
    }

    public function test_it_accepts_featured_source(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        $config = $schema->validate([
            'title' => 'Featured Products',
            'limit' => 8,
            'source' => [
                'type' => 'featured',
            ],
        ]);

        $this->assertSame(
            [
                'type' => 'featured',
            ],
            $config['source'],
        );
    }

    public function test_it_accepts_manual_source(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        $config = $schema->validate([
            'title' => 'Our Picks',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [7, 12, 19],
            ],
        ]);

        $this->assertSame(
            [
                'type' => 'manual',
                'product_ids' => [7, 12, 19],
            ],
            $config['source'],
        );
    }

    public function test_it_accepts_category_source(): void
    {
        $schema = new FeaturedProductsConfigSchema;

        $config = $schema->validate([
            'title' => 'Electronics',
            'limit' => 8,
            'source' => [
                'type' => 'category',
                'category_id' => 5,
            ],
        ]);

        $this->assertSame(
            [
                'type' => 'category',
                'category_id' => 5,
            ],
            $config['source'],
        );
    }
}
