<?php

declare(strict_types=1);

namespace Tests\Unit\WebsiteSettings;

use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\FooterConfigDefaults;
use App\Domain\FooterBuilder\Schemas\FooterConfigSchema;
use App\Domain\HeaderBuilder\Enums\HeaderTemplate;
use App\Domain\HeaderBuilder\HeaderConfigDefaults;
use App\Domain\HeaderBuilder\Schemas\HeaderConfigSchema;
use App\Domain\WebsiteSettings\BuilderStyleSchema;
use PHPUnit\Framework\TestCase;

final class BuilderStyleSchemaTest extends TestCase
{
    public function test_legacy_defaults_and_empty_style_keep_original_contracts(): void
    {
        self::assertSame(
            (new HeaderConfigDefaults)->for(HeaderTemplate::MegaMenu),
            (new HeaderConfigSchema)->validate(HeaderTemplate::MegaMenu, ['style' => []]),
        );

        foreach (FooterTemplate::cases() as $template) {
            self::assertSame(
                (new FooterConfigDefaults)->for($template),
                (new FooterConfigSchema)->validate($template, ['style' => ['main' => []]]),
            );
        }
    }

    public function test_valid_styles_round_trip_for_all_templates(): void
    {
        $style = ['main' => ['text_color' => '#aabbcc', 'background_color' => '#112233', 'text_size' => 16]];
        $config = ['style' => ['main' => ['text_color' => '#AABBCC', 'background_color' => '#112233', 'text_size' => 16]]];

        $header = (new HeaderConfigSchema)->validate(HeaderTemplate::MegaMenu, $config);
        self::assertSame($style, $header['style']);
        self::assertSame($header, (new HeaderConfigSchema)->validate(HeaderTemplate::MegaMenu, $header));

        foreach (FooterTemplate::cases() as $template) {
            $footer = (new FooterConfigSchema)->validate($template, $config);
            self::assertSame($style, $footer['style']);
            self::assertSame($footer, (new FooterConfigSchema)->validate($template, $footer));
        }
    }

    public function test_rejects_invalid_css_fields_and_section_names(): void
    {
        $errors = [];
        $result = (new BuilderStyleSchema)->normalize([
            'main' => [
                'text_color' => '#fff;position:fixed',
                'background_color' => 'url(https://example.com/tracker)',
                'text_size' => 1000,
                'custom_css' => 'display:none',
            ],
            'bottom' => ['text_size' => 12],
            'navigation' => 'not an object',
        ], ['main', 'navigation'], $errors);

        self::assertSame([], $result);
        self::assertSame([
            'style.main.text_color',
            'style.main.background_color',
            'style.main.text_size',
            'style.main.custom_css',
            'style.bottom',
            'style.navigation',
        ], array_keys($errors));
    }

    public function test_size_must_be_integer_and_style_must_be_an_object(): void
    {
        foreach (['16', 16.5, false, 9, 25, null] as $size) {
            $errors = [];
            self::assertSame([], (new BuilderStyleSchema)->normalize(['main' => ['text_size' => $size]], ['main'], $errors));
            self::assertArrayHasKey('style.main.text_size', $errors);
        }

        $errors = [];
        self::assertSame([], (new BuilderStyleSchema)->normalize('body { color: red; }', ['main'], $errors));
        self::assertArrayHasKey('style', $errors);
    }
}
