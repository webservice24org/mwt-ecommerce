<?php

declare(strict_types=1);

namespace Tests\Unit\FooterBuilder;

use App\Domain\FooterBuilder\Data\FooterTemplateData;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use App\Domain\FooterBuilder\Registry\FooterTemplateRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FooterTemplateRegistryTest extends TestCase
{
    public function test_footer_template_values_are_stable(): void
    {
        $this->assertSame(
            [
                'luxe_newsletter',
                'minimal_localized',
                'marketplace_trust',
            ],
            array_map(
                static fn (
                    FooterTemplate $template,
                ): string => $template->value,
                FooterTemplate::cases(),
            ),
        );
    }

    public function test_footer_template_labels_are_stable(): void
    {
        $this->assertSame(
            'Luxe Newsletter',
            FooterTemplate::LuxeNewsletter
                ->label(),
        );

        $this->assertSame(
            'Minimal Localization',
            FooterTemplate::MinimalLocalized
                ->label(),
        );

        $this->assertSame(
            'Marketplace Trust',
            FooterTemplate::MarketplaceTrust
                ->label(),
        );
    }

    public function test_registry_exposes_all_footer_templates(): void
    {
        $registry =
            new FooterTemplateRegistry;

        $templates =
            $registry->all();

        $this->assertCount(
            3,
            $templates,
        );

        foreach (
            $templates as $template
        ) {
            $this->assertInstanceOf(
                FooterTemplateData::class,
                $template,
            );
        }

        $this->assertSame(
            [
                'luxe_newsletter',
                'minimal_localized',
                'marketplace_trust',
            ],
            array_map(
                static fn (
                    FooterTemplateData $template,
                ): string => $template->key,
                $templates,
            ),
        );
    }

    public function test_registry_exposes_template_labels(): void
    {
        $registry =
            new FooterTemplateRegistry;

        $this->assertSame(
            [
                'Luxe Newsletter',
                'Minimal Localization',
                'Marketplace Trust',
            ],
            array_map(
                static fn (
                    FooterTemplateData $template,
                ): string => $template->label,
                $registry->all(),
            ),
        );
    }

    public function test_every_template_has_description(): void
    {
        $registry =
            new FooterTemplateRegistry;

        foreach (
            $registry->all() as $template
        ) {
            $this->assertNotSame(
                '',
                trim(
                    $template->description,
                ),
            );
        }
    }

    public function test_luxe_newsletter_is_default_template(): void
    {
        $registry =
            new FooterTemplateRegistry;

        $this->assertSame(
            FooterTemplate::LuxeNewsletter,
            $registry->default(),
        );
    }

    public function test_registry_supports_registered_template_keys(): void
    {
        $registry =
            new FooterTemplateRegistry;

        foreach (
            [
                'luxe_newsletter',
                'minimal_localized',
                'marketplace_trust',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supports(
                    $template,
                ),
            );
        }
    }

    public function test_registry_rejects_unknown_template_key(): void
    {
        $registry =
            new FooterTemplateRegistry;

        $this->assertFalse(
            $registry->supports(
                'unknown_footer',
            ),
        );
    }

    public function test_registry_can_get_template_by_enum(): void
    {
        $registry =
            new FooterTemplateRegistry;

        $template =
            $registry->get(
                FooterTemplate::MarketplaceTrust,
            );

        $this->assertSame(
            FooterTemplate::MarketplaceTrust,
            $template->template,
        );

        $this->assertSame(
            'marketplace_trust',
            $template->key,
        );

        $this->assertSame(
            'Marketplace Trust',
            $template->label,
        );
    }

    public function test_registry_can_get_template_by_key(): void
    {
        $registry =
            new FooterTemplateRegistry;

        $template =
            $registry->getByKey(
                'minimal_localized',
            );

        $this->assertSame(
            FooterTemplate::MinimalLocalized,
            $template->template,
        );

        $this->assertSame(
            'minimal_localized',
            $template->key,
        );
    }

    public function test_unknown_template_lookup_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new FooterTemplateRegistry
        )->getByKey(
            'unknown_footer',
        );
    }

    public function test_template_data_is_json_safe(): void
    {
        $template =
            (
                new FooterTemplateRegistry
            )->get(
                FooterTemplate::LuxeNewsletter,
            );

        $this->assertSame(
            [
                'key' => 'luxe_newsletter',

                'label' => 'Luxe Newsletter',

                'description' => FooterTemplate::LuxeNewsletter
                    ->description(),
            ],
            $template->toArray(),
        );
    }
}
