<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Domain\PageBuilder\Sections\FeaturesBenefitsSection;
use App\Models\PageSection;
use Tests\TestCase;

final class FeaturesBenefitsPageSectionResolverTest extends TestCase
{
    public function test_features_benefits_section_is_resolved_without_external_data(): void
    {
        $config = (
            new FeaturesBenefitsSection
        )->defaultConfigForTemplate(
            'icon_grid',
        );

        $section =
            new PageSection([
                'type' => SectionType::FeaturesBenefits,

                'template' => 'icon_grid',

                'config' => $config,
            ]);

        $section->id = 701;

        $resolved = app(
            PageSectionResolver::class,
        )->resolve(
            $section,
        );

        $this->assertSame(
            701,
            $resolved->id,
        );

        $this->assertSame(
            SectionType::FeaturesBenefits,
            $resolved->type,
        );

        $this->assertSame(
            'icon_grid',
            $resolved->template,
        );

        $this->assertSame(
            $config,
            $resolved->config,
        );

        $this->assertSame(
            [],
            $resolved->data,
        );
    }
}
