<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Resolvers\PageSectionResolver;
use App\Models\PageSection;
use Tests\TestCase;

final class CallToActionPageSectionResolverTest extends TestCase
{
    public function test_call_to_action_section_is_resolved_without_external_data(): void
    {
        $section = new PageSection([
            'type' => SectionType::CallToAction,
            'template' => 'high_impact',
            'config' => [
                'eyebrow' => 'Ready?',
                'heading' => 'Let us talk',
                'description' => 'Contact our team.',
                'background_type' => 'color',
                'background_color' => '#0f172a',
                'background_image' => null,
                'background_overlay' => 70,
                'text_theme' => 'light',
                'phone_label' => 'Call Us',
                'phone_number' => null,
                'email_label' => 'Email Us',
                'email' => null,
                'whatsapp_label' => 'WhatsApp',
                'whatsapp_number' => null,
                'whatsapp_message' => null,
                'newsletter_placeholder' => null,
                'newsletter_button_label' => null,
                'newsletter_note' => null,
            ],
        ]);

        $section->id = 601;

        $resolved = app(
            PageSectionResolver::class,
        )->resolve(
            $section,
        );

        $this->assertSame(
            601,
            $resolved->id,
        );

        $this->assertSame(
            SectionType::CallToAction,
            $resolved->type,
        );

        $this->assertSame(
            'high_impact',
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
