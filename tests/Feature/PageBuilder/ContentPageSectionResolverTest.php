<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Models\PageSection;
use Tests\TestCase;

final class ContentPageSectionResolverTest extends TestCase
{
    public function test_content_section_is_resolved_without_external_data(): void
    {
        $section = new PageSection([
            'type' => SectionType::Content,
            'template' => 'text',
            'config' => [
                'heading' => 'About Us',
                'body' => 'We make shopping simple.',
                'image' => null,
                'image_alt' => null,
                'alignment' => 'left',
            ],
        ]);

        $section->id = 501;

        $resolved = app(
            PageSectionResolver::class,
        )->resolve(
            $section,
        );

        $this->assertSame(
            501,
            $resolved->id,
        );

        $this->assertSame(
            SectionType::Content,
            $resolved->type,
        );

        $this->assertSame(
            'text',
            $resolved->template,
        );

        $this->assertSame(
            $section->config,
            $resolved->config,
        );

        $this->assertSame(
            [],
            $resolved->data,
        );
    }
}
