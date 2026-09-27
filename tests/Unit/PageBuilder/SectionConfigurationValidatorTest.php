<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Services\SectionConfigurationValidator;
use Tests\TestCase;

final class SectionConfigurationValidatorTest extends TestCase
{
    private SectionConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = app(
            SectionConfigurationValidator::class,
        );
    }

    public function test_registered_section_with_supported_template_is_validated(): void
    {
        $config = $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'grid',
            config: [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
        );

        $this->assertSame(
            [
                'title' => 'Featured Products',
                'limit' => 8,
                'source' => [
                    'type' => 'featured',
                ],
            ],
            $config,
        );
    }

    public function test_unregistered_section_type_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->validator->validate(
            type: SectionType::Hero,
            template: 'default',
            config: [],
        );
    }

    public function test_unsupported_template_is_rejected(): void
    {
        $this->expectException(
            InvalidSectionConfiguration::class,
        );

        $this->validator->validate(
            type: SectionType::FeaturedProducts,
            template: 'unsupported-template',
            config: [
                'title' => 'Featured Products',
                'limit' => 8,
            ],
        );
    }
}
