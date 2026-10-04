<?php

declare(strict_types=1);

namespace App\Domain\PageBuilder\Sections;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Contracts\SectionConfigSchema;
use App\Domain\PageBuilder\Sections\Contracts\SectionDefinition;
use App\Domain\PageBuilder\Sections\Schemas\PromotionalBannerConfigSchema;
use InvalidArgumentException;

final class PromotionalBannerSection implements SectionDefinition
{
    public function type(): SectionType
    {
        return SectionType::PromotionalBanner;
    }

    public function label(): string
    {
        return SectionType::PromotionalBanner
            ->label();
    }

    /**
     * @return list<SectionTemplateData>
     */
    public function templates(): array
    {
        return [
            new SectionTemplateData(
                key: 'image_banner',
                label: 'Image Banner',
                description: 'Display a promotional banner with an image, overlay content, and optional call-to-action.',
                category: 'Marketing',
            ),

            new SectionTemplateData(
                key: 'content_banner',
                label: 'Content Banner',
                description: 'Display a text-focused promotional banner with an optional call-to-action.',
                category: 'Marketing',
            ),

            new SectionTemplateData(
                key: 'split_banner',
                label: 'Split Banner',
                description: 'Display promotional content and an image side-by-side with an optional call-to-action.',
                category: 'Marketing',
            ),
        ];
    }

    public function defaultTemplate(): string
    {
        return 'image_banner';
    }

    /**
     * @return array<string, mixed>
     */
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
        return match ($template) {
            'image_banner' => [
                'heading' => 'Special Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'center',
            ],

            'content_banner' => [
                'heading' => 'Special Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'center',
            ],

            'split_banner' => [
                'heading' => 'Special Offer',
                'description' => '',
                'image' => null,
                'cta_label' => null,
                'cta_url' => null,
                'alignment' => 'left',
            ],

            default => throw new InvalidArgumentException(
                "Unsupported promotional banner template [{$template}].",
            ),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function templateDefaultConfigs(): array
    {
        return [
            'image_banner' => $this->defaultConfigForTemplate(
                'image_banner',
            ),

            'content_banner' => $this->defaultConfigForTemplate(
                'content_banner',
            ),

            'split_banner' => $this->defaultConfigForTemplate(
                'split_banner',
            ),
        ];
    }

    public function configSchema(): SectionConfigSchema
    {
        return new PromotionalBannerConfigSchema;
    }
}
