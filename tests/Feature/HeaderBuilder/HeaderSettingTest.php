<?php

declare(strict_types=1);

namespace Tests\Feature\HeaderBuilder;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;
use App\Models\HeaderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HeaderSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_singleton_creates_global_header_with_defaults(): void
    {
        $header =
            HeaderSetting::singleton();

        $this->assertSame(
            HeaderSetting::SINGLETON_KEY,
            $header->singleton_key,
        );

        $this->assertSame(
            HeaderTemplate::MegaMenu,
            $header->template,
        );

        $this->assertTrue(
            $header->is_enabled,
        );

        $this->assertSame(
            (
                new HeaderConfigDefaults
            )->for(
                HeaderTemplate::MegaMenu,
            ),
            $header->config,
        );

        $this->assertDatabaseCount(
            'header_settings',
            1,
        );
    }

    public function test_singleton_does_not_create_duplicate_rows(): void
    {
        $first =
            HeaderSetting::singleton();

        $second =
            HeaderSetting::singleton();

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertDatabaseCount(
            'header_settings',
            1,
        );
    }

    public function test_empty_stored_config_resolves_to_template_defaults(): void
    {
        $header =
            HeaderSetting::query()->create([
                'singleton_key' => HeaderSetting::SINGLETON_KEY,

                'template' => HeaderTemplate::MegaMenu,

                'config' => [],

                'is_enabled' => true,
            ]);

        $this->assertSame(
            [],
            $header->config,
        );

        $this->assertSame(
            (
                new HeaderConfigDefaults
            )->for(
                HeaderTemplate::MegaMenu,
            ),
            $header->resolvedConfig(),
        );

        /*
         * Resolving defaults must not silently
         * mutate persisted configuration.
         */
        $header->refresh();

        $this->assertSame(
            [],
            $header->config,
        );
    }
}
