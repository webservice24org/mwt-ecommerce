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

final class CallToActionHighImpactStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_high_impact_call_to_action(): void
    {
        $page = Page::factory()->create([
            'title' => 'Contact Us',
            'slug' => 'contact-us',
            'status' => PageStatus::Published,
            'content_mode' => PageContentMode::Builder,
            'published_at' => now()->subMinute(),
        ]);

        $section = $page
            ->sections()
            ->create([
                'type' => SectionType::CallToAction,
                'template' => 'high_impact',
                'position' => 10,
                'is_enabled' => true,
                'layout' => [
                    'width' => SectionWidth::Container->value,
                ],
                'config' => [
                    'eyebrow' => 'Ready to get started?',
                    'heading' => 'Let\'s build something great together',
                    'description' => 'Reach us directly by WhatsApp, phone, or email to discuss your next project.',

                    'background_type' => 'image',
                    'background_color' => '#0f172a',
                    'background_image' => '/storage/page-builder/cta/high-impact.webp',
                    'background_overlay' => 70,

                    'text_theme' => 'light',

                    'phone_label' => 'Or call directly',
                    'phone_number' => '+880 1700-000000',

                    'email_label' => 'Email Us',
                    'email' => 'hello@example.com',

                    'whatsapp_label' => 'Chat on WhatsApp',
                    'whatsapp_number' => '+880 1700-000000',
                    'whatsapp_message' => 'Hello, I would like to discuss a project.',

                    'newsletter_placeholder' => null,
                    'newsletter_button_label' => null,
                    'newsletter_note' => null,
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
                        'high_impact',
                    )
                    ->where(
                        'page.sections.0.layout.width',
                        SectionWidth::Container->value,
                    )
                    ->where(
                        'page.sections.0.config.eyebrow',
                        'Ready to get started?',
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Let\'s build something great together',
                    )
                    ->where(
                        'page.sections.0.config.background_type',
                        'image',
                    )
                    ->where(
                        'page.sections.0.config.background_color',
                        '#0f172a',
                    )
                    ->where(
                        'page.sections.0.config.background_image',
                        '/storage/page-builder/cta/high-impact.webp',
                    )
                    ->where(
                        'page.sections.0.config.background_overlay',
                        70,
                    )
                    ->where(
                        'page.sections.0.config.text_theme',
                        'light',
                    )
                    ->where(
                        'page.sections.0.config.phone_number',
                        '+880 1700-000000',
                    )
                    ->where(
                        'page.sections.0.config.email',
                        'hello@example.com',
                    )
                    ->where(
                        'page.sections.0.config.whatsapp_number',
                        '+880 1700-000000',
                    )
                    ->where(
                        'page.sections.0.config.whatsapp_message',
                        'Hello, I would like to discuss a project.',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }
}
