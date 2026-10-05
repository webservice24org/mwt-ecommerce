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

final class CallToActionContactGridStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_contact_grid_call_to_action(): void
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

                'template' => 'contact_grid',

                'position' => 10,

                'is_enabled' => true,

                'layout' => [
                    'width' => SectionWidth::Container->value,
                ],

                'config' => [
                    'eyebrow' => null,

                    'heading' => 'Have questions? Get in touch with us',

                    'description' => 'Reach out through your preferred channel for support or project inquiries.',

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
                        'contact_grid',
                    )
                    ->where(
                        'page.sections.0.layout.width',
                        SectionWidth::Container->value,
                    )
                    ->where(
                        'page.sections.0.config.heading',
                        'Have questions? Get in touch with us',
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
                        'page.sections.0.config.text_theme',
                        'dark',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    ),
            );
    }
}
