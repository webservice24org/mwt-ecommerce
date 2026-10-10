<?php

declare(strict_types=1);

namespace Tests\Unit\HeaderBuilder;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\Exceptions\InvalidHeaderConfiguration;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;
use App\Domain\HeaderBuilder\Schemas\HeaderConfigSchema;
use PHPUnit\Framework\TestCase;

final class HeaderConfigSchemaTest extends TestCase
{
    public function test_empty_config_resolves_to_defaults(): void
    {
        $schema =
            new HeaderConfigSchema;

        $this->assertSame(
            (
                new HeaderConfigDefaults
            )->for(
                HeaderTemplate::MegaMenu,
            ),
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [],
            ),
        );
    }

    public function test_valid_partial_config_is_normalized(): void
    {
        $schema =
            new HeaderConfigSchema;

        $config =
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [
                    'brand' => [
                        'name' => 'MWT Store',
                    ],
                ],
            );

        $this->assertSame(
            'MWT Store',
            $config['brand']['name'],
        );

        $this->assertSame(
            '.',
            $config['brand']['accent'],
        );

        $this->assertTrue(
            $config['search']['enabled'],
        );
    }

    public function test_unknown_top_level_key_is_rejected(): void
    {
        $schema =
            new HeaderConfigSchema;

        try {
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [
                    'custom_css' => 'position: fixed;',
                ],
            );

            $this->fail(
                'Expected invalid header configuration exception.',
            );
        } catch (
            InvalidHeaderConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'custom_css',
                $exception->errors,
            );
        }
    }

    public function test_unknown_nested_key_is_rejected(): void
    {
        $schema =
            new HeaderConfigSchema;

        try {
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [
                    'brand' => [
                        'name' => 'MWT Store',

                        'javascript' => 'alert(1)',
                    ],
                ],
            );

            $this->fail(
                'Expected invalid header configuration exception.',
            );
        } catch (
            InvalidHeaderConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'brand.javascript',
                $exception->errors,
            );
        }
    }

    public function test_invalid_navigation_style_is_rejected(): void
    {
        $schema =
            new HeaderConfigSchema;

        try {
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [
                    'navigation' => [
                        'links' => [
                            [
                                'label' => 'Sale',

                                'url' => '#',

                                'style' => 'dangerous-style',
                            ],
                        ],
                    ],
                ],
            );

            $this->fail(
                'Expected invalid header configuration exception.',
            );
        } catch (
            InvalidHeaderConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'navigation.links.0.style',
                $exception->errors,
            );
        }
    }

    public function test_too_many_mega_menu_groups_are_rejected(): void
    {
        $schema =
            new HeaderConfigSchema;

        $groups = [];

        for (
            $index = 0;
            $index < 7;
            $index++
        ) {
            $groups[] = [
                'heading' => "Group {$index}",

                'links' => [],
            ];
        }

        try {
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [
                    'mega_menu' => [
                        'groups' => $groups,
                    ],
                ],
            );

            $this->fail(
                'Expected invalid header configuration exception.',
            );
        } catch (
            InvalidHeaderConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'mega_menu.groups',
                $exception->errors,
            );
        }
    }

    public function test_non_boolean_enabled_value_is_rejected(): void
    {
        $schema =
            new HeaderConfigSchema;

        try {
            $schema->validate(
                HeaderTemplate::MegaMenu,
                [
                    'search' => [
                        'enabled' => 'yes',
                    ],
                ],
            );

            $this->fail(
                'Expected invalid header configuration exception.',
            );
        } catch (
            InvalidHeaderConfiguration $exception
        ) {
            $this->assertArrayHasKey(
                'search.enabled',
                $exception->errors,
            );
        }
    }
}
