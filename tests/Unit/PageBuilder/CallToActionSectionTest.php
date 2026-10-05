<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\CallToActionSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CallToActionSectionTest extends TestCase
{
    public function test_call_to_action_section_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        $this->assertCount(
            7,
            $registry->all(),
        );

        $this->assertTrue(
            $registry->has(
                SectionType::CallToAction,
            ),
        );

        $this->assertInstanceOf(
            CallToActionSection::class,
            $registry->get(
                SectionType::CallToAction,
            ),
        );
    }

    public function test_call_to_action_section_exposes_three_typed_templates(): void
    {
        $definition = (
            new SectionRegistry
        )->get(
            SectionType::CallToAction,
        );

        $templates =
            $definition->templates();

        $this->assertCount(
            3,
            $templates,
        );

        foreach ($templates as $template) {
            $this->assertInstanceOf(
                SectionTemplateData::class,
                $template,
            );
        }

        $this->assertSame(
            [
                'high_impact',
                'split_lead_capture',
                'contact_grid',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->key,
                $templates,
            ),
        );

        $this->assertSame(
            [
                'High Impact',
                'Split Lead Capture',
                'Contact Grid',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->label,
                $templates,
            ),
        );

        $this->assertSame(
            [
                'Marketing',
                'Marketing',
                'Marketing',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->category,
                $templates,
            ),
        );
    }

    public function test_call_to_action_section_supports_all_three_templates(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            [
                'high_impact',
                'split_lead_capture',
                'contact_grid',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::CallToAction,
                    $template,
                ),
            );
        }
    }

    public function test_every_call_to_action_template_default_is_valid(): void
    {
        $definition =
            new CallToActionSection;

        $this->assertSame(
            'high_impact',
            $definition->defaultTemplate(),
        );

        foreach (
            [
                'high_impact',
                'split_lead_capture',
                'contact_grid',
            ] as $template
        ) {
            $config =
                $definition
                    ->defaultConfigForTemplate(
                        $template,
                    );

            $this->assertSame(
                $config,
                $definition
                    ->configSchema()
                    ->validate(
                        $config,
                    ),
            );
        }
    }

    public function test_template_defaults_include_background_choice_and_contact_contract(): void
    {
        $definition =
            new CallToActionSection;

        $highImpact =
            $definition
                ->defaultConfigForTemplate(
                    'high_impact',
                );

        $split =
            $definition
                ->defaultConfigForTemplate(
                    'split_lead_capture',
                );

        $contactGrid =
            $definition
                ->defaultConfigForTemplate(
                    'contact_grid',
                );

        $this->assertSame(
            'color',
            $highImpact[
                'background_type'
            ],
        );

        $this->assertSame(
            '#0f172a',
            $highImpact[
                'background_color'
            ],
        );

        $this->assertNull(
            $highImpact[
                'background_image'
            ],
        );

        $this->assertSame(
            'Subscribe',
            $split[
                'newsletter_button_label'
            ],
        );

        $this->assertSame(
            'dark',
            $contactGrid[
                'text_theme'
            ],
        );

        $this->assertSame(
            [
                'high_impact',
                'split_lead_capture',
                'contact_grid',
            ],
            array_keys(
                $definition
                    ->templateDefaultConfigs(),
            ),
        );
    }

    public function test_unknown_call_to_action_template_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new CallToActionSection
        )->defaultConfigForTemplate(
            'unknown',
        );
    }
}
