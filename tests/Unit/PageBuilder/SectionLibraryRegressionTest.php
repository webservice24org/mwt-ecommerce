<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionTemplateData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class SectionLibraryRegressionTest extends TestCase
{
    /**
     * These are the section types delivered by
     * the 4.15 Section Library roadmap.
     *
     * @return list<SectionType>
     */
    private function sectionLibraryTypes(): array
    {
        return [
            SectionType::ProductCollection,
            SectionType::ProductCategories,
            SectionType::PromotionalBanner,
            SectionType::Content,
            SectionType::CallToAction,
            SectionType::FeaturesBenefits,
            SectionType::Brands,
            SectionType::Testimonials,
            SectionType::Faq,
            SectionType::SpacerDivider,
        ];
    }

    public function test_every_section_library_type_is_registered(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $this->assertTrue(
                $registry->has(
                    $type,
                ),
                sprintf(
                    'Expected section type [%s] to be registered.',
                    $type->value,
                ),
            );
        }
    }

    public function test_every_section_library_definition_has_a_label(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $definition =
                $registry->get(
                    $type,
                );

            $this->assertNotSame(
                '',
                trim(
                    $type->label(),
                ),
                sprintf(
                    'Section type [%s] must have a label.',
                    $type->value,
                ),
            );

            $this->assertNotSame(
                '',
                trim(
                    $definition->label(),
                ),
                sprintf(
                    'Section definition [%s] must have a label.',
                    $type->value,
                ),
            );
        }
    }

    public function test_every_section_library_definition_has_templates(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $templates =
                $registry
                    ->get(
                        $type,
                    )
                    ->templates();

            $this->assertNotEmpty(
                $templates,
                sprintf(
                    'Section type [%s] must expose at least one template.',
                    $type->value,
                ),
            );

            foreach (
                $templates as $template
            ) {
                $this->assertInstanceOf(
                    SectionTemplateData::class,
                    $template,
                );
            }
        }
    }

    public function test_template_keys_are_unique_inside_each_section_type(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $templates =
                $registry
                    ->get(
                        $type,
                    )
                    ->templates();

            $keys =
                array_map(
                    static fn (
                        SectionTemplateData $template,
                    ): string => $template->key,
                    $templates,
                );

            $this->assertSame(
                count(
                    $keys,
                ),
                count(
                    array_unique(
                        $keys,
                    ),
                ),
                sprintf(
                    'Section type [%s] contains duplicate template keys.',
                    $type->value,
                ),
            );
        }
    }

    public function test_every_template_has_complete_library_metadata(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $templates =
                $registry
                    ->get(
                        $type,
                    )
                    ->templates();

            foreach (
                $templates as $template
            ) {
                $this->assertNotSame(
                    '',
                    trim(
                        $template->key,
                    ),
                );

                $this->assertNotSame(
                    '',
                    trim(
                        $template->label,
                    ),
                );

                $this->assertNotSame(
                    '',
                    trim(
                        $template->category,
                    ),
                );
            }
        }
    }

    public function test_every_default_template_is_supported_by_registry(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $definition =
                $registry->get(
                    $type,
                );

            $defaultTemplate =
                $definition
                    ->defaultTemplate();

            $this->assertTrue(
                $registry->supportsTemplate(
                    $type,
                    $defaultTemplate,
                ),
                sprintf(
                    'Default template [%s] is not supported for section [%s].',
                    $defaultTemplate,
                    $type->value,
                ),
            );
        }
    }

    public function test_every_declared_template_is_supported_by_registry(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $templates =
                $registry
                    ->get(
                        $type,
                    )
                    ->templates();

            foreach (
                $templates as $template
            ) {
                $this->assertTrue(
                    $registry->supportsTemplate(
                        $type,
                        $template->key,
                    ),
                    sprintf(
                        'Template [%s] is not supported for section [%s].',
                        $template->key,
                        $type->value,
                    ),
                );
            }
        }
    }

    public function test_every_template_default_configuration_passes_its_schema(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $definition =
                $registry->get(
                    $type,
                );

            foreach (
                $definition->templates() as $template
            ) {
                $config =
                    $definition
                        ->defaultConfigForTemplate(
                            $template->key,
                        );

                $this->assertSame(
                    $config,
                    $definition
                        ->configSchema()
                        ->validate(
                            $config,
                        ),
                    sprintf(
                        'Default configuration for [%s:%s] did not round-trip through its schema.',
                        $type->value,
                        $template->key,
                    ),
                );
            }
        }
    }

    public function test_every_section_default_config_matches_its_default_template(): void
    {
        $registry =
            new SectionRegistry;

        foreach (
            $this->sectionLibraryTypes() as $type
        ) {
            $definition =
                $registry->get(
                    $type,
                );

            $this->assertSame(
                $definition
                    ->defaultConfigForTemplate(
                        $definition
                            ->defaultTemplate(),
                    ),
                $definition
                    ->defaultConfig(),
                sprintf(
                    'Default config mismatch for section [%s].',
                    $type->value,
                ),
            );
        }
    }
}
