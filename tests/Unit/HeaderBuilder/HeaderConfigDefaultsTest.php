<?php

declare(strict_types=1);

namespace Tests\Unit\HeaderBuilder;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;
use PHPUnit\Framework\TestCase;

final class HeaderConfigDefaultsTest extends TestCase
{
    public function test_mega_menu_has_complete_default_configuration(): void
    {
        $config = (
            new HeaderConfigDefaults
        )->for(
            HeaderTemplate::MegaMenu,
        );

        $this->assertArrayHasKey(
            'announcement',
            $config,
        );

        $this->assertArrayHasKey(
            'brand',
            $config,
        );

        $this->assertArrayHasKey(
            'search',
            $config,
        );

        $this->assertArrayHasKey(
            'actions',
            $config,
        );

        $this->assertArrayHasKey(
            'navigation',
            $config,
        );

        $this->assertArrayHasKey(
            'mega_menu',
            $config,
        );

        $this->assertArrayHasKey(
            'mobile',
            $config,
        );

        $this->assertSame(
            'MWT STORE',
            $config['brand']['name'],
        );

        $this->assertTrue(
            $config['announcement']['enabled'],
        );

        $this->assertTrue(
            $config['search']['enabled'],
        );

        $this->assertTrue(
            $config['mega_menu']['enabled'],
        );

        $this->assertCount(
            2,
            $config['mega_menu']['groups'],
        );

        $this->assertSame(
            "Women's Apparel",
            $config['mega_menu']['groups'][0]['heading'],
        );
    }
}
