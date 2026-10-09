<?php

declare(strict_types=1);

namespace Tests\Feature\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Models\FooterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_singleton_creates_global_footer_setting(): void
    {
        $footer =
            FooterSetting::singleton();

        $expectedConfig =
            (
                new FooterConfigDefaults
            )->for(
                FooterTemplate::LuxeNewsletter,
            );

        $this->assertDatabaseCount(
            'footer_settings',
            1,
        );

        $this->assertSame(
            FooterSetting::SINGLETON_KEY,
            $footer->singleton_key,
        );

        $this->assertSame(
            FooterTemplate::LuxeNewsletter,
            $footer->template,
        );

        $this->assertSame(
            $expectedConfig,
            $footer->config,
        );

        $this->assertTrue(
            $footer->is_enabled,
        );
    }

    public function test_singleton_returns_existing_record_instead_of_creating_another(): void
    {
        $first =
            FooterSetting::singleton();

        $second =
            FooterSetting::singleton();

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertDatabaseCount(
            'footer_settings',
            1,
        );
    }

    public function test_singleton_does_not_overwrite_existing_configuration(): void
    {
        $existing =
            FooterSetting::query()->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MarketplaceTrust,

                'config' => [
                    'custom_existing_value' => 'keep-me',
                ],

                'is_enabled' => false,
            ]);

        $footer =
            FooterSetting::singleton();

        $this->assertSame(
            $existing->id,
            $footer->id,
        );

        $this->assertSame(
            FooterTemplate::MarketplaceTrust,
            $footer->template,
        );

        $this->assertSame(
            [
                'custom_existing_value' => 'keep-me',
            ],
            $footer->config,
        );

        $this->assertFalse(
            $footer->is_enabled,
        );

        $this->assertDatabaseCount(
            'footer_settings',
            1,
        );
    }

    public function test_config_is_cast_to_array(): void
    {
        $footer =
            FooterSetting::query()->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::LuxeNewsletter,

                'config' => [
                    'copyright' => 'Example Store',
                ],

                'is_enabled' => true,
            ]);

        $footer->refresh();

        $this->assertIsArray(
            $footer->config,
        );

        $this->assertSame(
            'Example Store',
            $footer->config[
                'copyright'
            ],
        );
    }

    public function test_template_is_cast_to_footer_template_enum(): void
    {
        $footer =
            FooterSetting::query()->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::MinimalLocalized,

                'config' => [],

                'is_enabled' => true,
            ]);

        $footer->refresh();

        $this->assertSame(
            FooterTemplate::MinimalLocalized,
            $footer->template,
        );
    }

    public function test_is_enabled_is_cast_to_boolean(): void
    {
        $footer =
            FooterSetting::query()->create([
                'singleton_key' => FooterSetting::SINGLETON_KEY,

                'template' => FooterTemplate::LuxeNewsletter,

                'config' => [],

                'is_enabled' => 1,
            ]);

        $footer->refresh();

        $this->assertIsBool(
            $footer->is_enabled,
        );

        $this->assertTrue(
            $footer->is_enabled,
        );
    }
}
