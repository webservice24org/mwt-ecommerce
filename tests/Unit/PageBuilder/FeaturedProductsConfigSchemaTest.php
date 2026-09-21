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
}
