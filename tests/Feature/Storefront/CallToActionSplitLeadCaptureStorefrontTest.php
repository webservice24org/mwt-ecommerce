<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Enums\SectionWidth;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CallToActionSplitLeadCaptureStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_split_lead_capture_call_to_action(): void
    {
        $page = Page::factory()->create([
            'title' => 'Project Enquiry',
            'slug' => 'project-enquiry',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $section = $page
            ->sections()
            ->create([
                'type' => SectionType::CallToAction,
                'template' => 'split_lead_capture',
                'position' => 10,
                'is_enabled' => true,
                'layout' => [
                    'width' => SectionWidth::Container->value,
                ],
                'config' => [
                    'eyebrow' => null,

                    'heading' => 'Let\'s talk about your project',

                    'description' => 'Subscribe for updates or contact us directly by phone, WhatsApp, or email.',

                    'background_type' => 'color',

                    'background_color' => '#ffffff',

                    'background_image' => null,

                    'background_overlay' => 65,

                    'text_theme' => 'dark',

                    'phone_label' => 'Call Us',

                    'phone_number' => '+880 1700-000000',

                    'email_label' => 'Email Us',

                    'email' => 'hello@example.com',

                    'whatsapp_label' => 'WhatsApp',

                    'whatsapp_number' => '+880 1700-000000',

                    'whatsapp_message' => 'Hello, I would like to discuss a project.',

                    'newsletter_placeholder' => 'Enter your email address',

                    'newsletter_button_label' => 'Subscribe',

                    'newsletter_note' => 'No spam, unsubscribe anytime.',
                ],
            ]);

        $response = $this->get(
            route(
                'frontend.pages.show',
                $page->slug,
            ),
        );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $inertia) => $inertia
                    ->component(
                        'Frontend/Pages/Show',
                    )
                    ->has(
                        'page.sections',
                        1,
                    )
                    ->where(
                        'page.sections.0.id',
                        $section->id,
                    )
                    ->where(
                        'page.sections.0.type',
                        SectionType::CallToAction->value,
                    )
                    ->where(
                        'page.sections.0.template',
                        'split_lead_capture',
                    )
                    ->where(
                        'page.sections.0.layout.width',
                        SectionWidth::Container->value,
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Let\'s talk about your project',
                    )
                    ->where(
                        'page.sections.0.config.text_theme',
                        'dark',
                    )
                    ->where(
                        'page.sections.0.config.newsletter_placeholder',
                        'Enter your email address',
                    )
                    ->where(
                        'page.sections.0.config.newsletter_button_label',
                        'Subscribe',
                    )
                    ->where(
                        'page.sections.0.config.newsletter_note',
                        'No spam, unsubscribe anytime.',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }
}
