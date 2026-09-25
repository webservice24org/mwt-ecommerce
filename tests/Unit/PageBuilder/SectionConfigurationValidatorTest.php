<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Registry\SectionRegistry;
use App\Domain\PageBuilder\Sections\Exceptions\InvalidSectionConfiguration;
use App\Domain\PageBuilder\Services\SectionConfigurationValidator;
use PHPUnit\Framework\TestCase;

final class SectionConfigurationValidatorTest extends TestCase
{
    public function test_registered_section_with_supported_template_is_validated(): void
    {
        $validator = new SectionConfigurationValidator(
            new SectionRegistry,
        );

        $config = $validator->validate(
            SectionType::FeaturedProducts,
            'grid',
            [
                'title' => ' Featured Products ',
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
        $validator = new SectionConfigurationValidator(
            new SectionRegistry,
        );

        try {
            $validator->validate(
                SectionType::Hero,
                'grid',
                [],
            );

            $this->fail(
                'Expected unregistered section type to be rejected.',
            );
        } catch (InvalidSectionConfiguration $exception) {
            $this->assertArrayHasKey(
                'type',
                $exception->errors(),
            );
        }
    }

    public function test_unsupported_template_is_rejected(): void
    {
        $validator = new SectionConfigurationValidator(
            new SectionRegistry,
        );

        try {
            $validator->validate(
                SectionType::FeaturedProducts,
                'carousel',
                [
                    'title' => 'Featured Products',
                    'limit' => 8,
                ],
            );

            $this->fail(
                'Expected unsupported template to be rejected.',
            );
        } catch (InvalidSectionConfiguration $exception) {
            $this->assertArrayHasKey(
                'template',
                $exception->errors(),
            );
        }
    }
}
