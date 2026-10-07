<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class FeaturesBenefitsDefinitionQueryTest extends TestCase
{
    public function test_features_benefits_definition_is_exposed_to_the_builder(): void
    {
        $definitions = (
            new GetSectionDefinitionsQuery(
                new SectionRegistry,
            )
        )->handle();

        $this->assertCount(
            12,
            $definitions,
        );

        $features =
            $definitions[7];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $features,
        );

        $this->assertSame(
            SectionType::FeaturesBenefits->value,
            $features->type,
        );

        $this->assertSame(
            'Features / Benefits',
            $features->label,
        );

        $this->assertSame(
            'icon_grid',
            $features->defaultTemplate,
        );

        $this->assertCount(
            3,
            $features->templates,
        );

        $this->assertSame(
            [
                'icon_grid',
                'image_grid',
                'horizontal_benefits',
            ],
            array_column(
                $features->templates,
                'key',
            ),
        );

        $this->assertSame(
            [
                'Icon Grid',
                'Image Grid',
                'Horizontal Benefits',
            ],
            array_column(
                $features->templates,
                'label',
            ),
        );

        $this->assertCount(
            3,
            $features
                ->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'icon_grid',
            $features
                ->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'image_grid',
            $features
                ->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'horizontal_benefits',
            $features
                ->templateDefaultConfigs,
        );

        $this->assertSame(
            $features
                ->templateDefaultConfigs[
                'icon_grid'
            ],
            $features->defaultConfig,
        );
    }
}
