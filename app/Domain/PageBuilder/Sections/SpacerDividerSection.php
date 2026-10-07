<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\SpacerDividerConfigSchema;
use InvalidArgumentException;

final class SpacerDividerSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::SpacerDivider;
    }

    public function label(): string
    {
        return $this->type()->label();
    }

    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'responsive_spacer',
                label: 'Responsive Spacer',
                description: 'Adaptive vertical whitespace with separate mobile, tablet, and desktop heights.',
                category: 'Layout',
            ),

            new SectionTemplateData(
                key: 'line_divider',
                label: 'Line Divider',
                description: 'A clean horizontal divider with solid, dashed, or dotted line styles.',
                category: 'Layout',
            ),

            new SectionTemplateData(
                key: 'label_divider',
                label: 'Label Divider',
                description: 'A horizontal divider with a centered or aligned text label.',
                category: 'Layout',
            ),

            new SectionTemplateData(
                key: 'gradient_divider',
                label: 'Gradient Divider',
                description: 'A decorative horizontal divider with a customizable three-color gradient.',
                category: 'Layout',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'responsive_spacer';
    }

    public function defaultConfig(): array
    {
        return $this->defaultConfigForTemplate(
            $this->defaultTemplate(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfigForTemplate(
        string $template,
    ): array {
        $configs =
            $this->templateDefaultConfigs();

        if (
            ! array_key_exists(
                $template,
                $configs,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown Spacer / Divider template [%s].',
                    $template,
                ),
            );
        }

        return $configs[$template];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'responsive_spacer' => [
                'mobile_height' => 32,
                'tablet_height' => 64,
                'desktop_height' => 96,

                'line_style' => 'solid',
                'line_color' => '#e2e8f0',
                'line_thickness' => 1,

                'width' => 'full',
                'alignment' => 'center',

                'label' => null,
                'label_style' => 'plain',
                'text_color' => '#64748b',

                'gradient_from' => '#6366f1',
                'gradient_via' => '#8b5cf6',
                'gradient_to' => '#ec4899',
            ],

            'line_divider' => [
                'mobile_height' => 24,
                'tablet_height' => 32,
                'desktop_height' => 40,

                'line_style' => 'solid',
                'line_color' => '#e2e8f0',
                'line_thickness' => 1,

                'width' => 'full',
                'alignment' => 'center',

                'label' => null,
                'label_style' => 'plain',
                'text_color' => '#64748b',

                'gradient_from' => '#6366f1',
                'gradient_via' => '#8b5cf6',
                'gradient_to' => '#ec4899',
            ],

            'label_divider' => [
                'mobile_height' => 24,
                'tablet_height' => 32,
                'desktop_height' => 40,

                'line_style' => 'solid',
                'line_color' => '#e2e8f0',
                'line_thickness' => 1,

                'width' => 'full',
                'alignment' => 'center',

                'label' => 'Continue Exploring',
                'label_style' => 'pill',
                'text_color' => '#4f46e5',

                'gradient_from' => '#6366f1',
                'gradient_via' => '#8b5cf6',
                'gradient_to' => '#ec4899',
            ],

            'gradient_divider' => [
                'mobile_height' => 24,
                'tablet_height' => 32,
                'desktop_height' => 40,

                'line_style' => 'solid',
                'line_color' => '#e2e8f0',
                'line_thickness' => 2,

                'width' => 'full',
                'alignment' => 'center',

                'label' => null,
                'label_style' => 'plain',
                'text_color' => '#64748b',

                'gradient_from' => '#6366f1',
                'gradient_via' => '#8b5cf6',
                'gradient_to' => '#ec4899',
            ],
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new SpacerDividerConfigSchema;
    }
}
