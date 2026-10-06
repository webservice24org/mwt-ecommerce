<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\FeaturesBenefitsSection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FeaturesBenefitsSectionTest extends TestCase
{
    public function test_features_benefits_section_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        $this->assertCount(
            8,
            $registry->all(),
        );

        $this->assertTrue(
            $registry->has(
                SectionType::FeaturesBenefits,
            ),
        );

        $this->assertInstanceOf(
            FeaturesBenefitsSection::class,
            $registry->get(
                SectionType::FeaturesBenefits,
            ),
        );
    }

    public function test_features_benefits_exposes_three_typed_templates(): void
    {
        $definition = (
            new SectionRegistry
        )->get(
            SectionType::FeaturesBenefits,
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
                'icon_grid',
                'image_grid',
                'horizontal_benefits',
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
                'Icon Grid',
                'Image Grid',
                'Horizontal Benefits',
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
                'Content',
                'Content',
                'Content',
            ],
            array_map(
                static fn (
                    SectionTemplateData $template,
                ): string => $template->category,
                $templates,
            ),
        );
    }

    public function test_registry_supports_all_features_benefits_templates(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            [
                'icon_grid',
                'image_grid',
                'horizontal_benefits',
            ] as $template
        ) {
            $this->assertTrue(
                $registry->supportsTemplate(
                    SectionType::FeaturesBenefits,
                    $template,
                ),
            );
        }
    }

    public function test_every_template_default_is_valid(): void
    {
        $definition =
            new FeaturesBenefitsSection;

        $this->assertSame(
            'icon_grid',
            $definition->defaultTemplate(),
        );

        foreach (
            [
                'icon_grid',
                'image_grid',
                'horizontal_benefits',
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

    public function test_template_defaults_include_expected_media_contract(): void
    {
        $definition =
            new FeaturesBenefitsSection;

        $iconGrid =
            $definition
                ->defaultConfigForTemplate(
                    'icon_grid',
                );

        $imageGrid =
            $definition
                ->defaultConfigForTemplate(
                    'image_grid',
                );

        $horizontal =
            $definition
                ->defaultConfigForTemplate(
                    'horizontal_benefits',
                );

        $this->assertSame(
            'truck',
            $iconGrid['items'][0][
                'icon'
            ],
        );

        $this->assertNull(
            $imageGrid['items'][0][
                'icon'
            ],
        );

        $this->assertNull(
            $imageGrid['items'][0][
                'image'
            ],
        );

        $this->assertSame(
            'left',
            $horizontal[
                'alignment'
            ],
        );

        $this->assertSame(
            [
                'icon_grid',
                'image_grid',
                'horizontal_benefits',
            ],
            array_keys(
                $definition
                    ->templateDefaultConfigs(),
            ),
        );
    }

    public function test_unknown_features_benefits_template_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'unknown',
        );
    }
}
