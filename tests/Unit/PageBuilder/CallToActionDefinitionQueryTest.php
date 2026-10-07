<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Data\SectionDefinitionData;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Queries\GetSectionDefinitionsQuery;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use PHPUnit\Framework\TestCase;

final class CallToActionDefinitionQueryTest extends TestCase
{
    public function test_call_to_action_definition_is_exposed_to_the_builder(): void
    {
        $definitions = (
            new GetSectionDefinitionsQuery(
                new SectionRegistry,
            )
        )->handle();

        $this->assertCount(
            10,
            $definitions,
        );

        $cta =
            $definitions[6];

        $this->assertInstanceOf(
            SectionDefinitionData::class,
            $cta,
        );

        $this->assertSame(
            SectionType::CallToAction->value,
            $cta->type,
        );

        $this->assertSame(
            'Call to Action',
            $cta->label,
        );

        $this->assertSame(
            'high_impact',
            $cta->defaultTemplate,
        );

        $this->assertCount(
            3,
            $cta->templates,
        );

        $this->assertSame(
            [
                'high_impact',
                'split_lead_capture',
                'contact_grid',
            ],
            array_column(
                $cta->templates,
                'key',
            ),
        );

        $this->assertSame(
            [
                'High Impact',
                'Split Lead Capture',
                'Contact Grid',
            ],
            array_column(
                $cta->templates,
                'label',
            ),
        );

        $this->assertCount(
            3,
            $cta->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'high_impact',
            $cta->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'split_lead_capture',
            $cta->templateDefaultConfigs,
        );

        $this->assertArrayHasKey(
            'contact_grid',
            $cta->templateDefaultConfigs,
        );

        $this->assertSame(
            $cta->templateDefaultConfigs[
                'high_impact'
            ],
            $cta->defaultConfig,
        );
    }
}
