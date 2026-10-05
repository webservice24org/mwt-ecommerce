<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\CallToActionSection;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class CallToActionSectionStorefrontRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_builder_page_exposes_all_three_call_to_action_templates_in_order(): void
    {
        $page = Page::factory()->create([
            'title' => 'CTA Showcase',

            'slug' => 'cta-showcase',

            'status' => PageStatus::Published,

            'content_mode' => PageContentMode::Builder,

            'published_at' => now()->subMinute(),
        ]);

        $definition =
            new CallToActionSection;

        $highImpactConfig =
            array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'high_impact',
                    ),
                [
                    'background_type' => 'image',

                    'background_color' => '#0f172a',

                    'background_image' => '/storage/page-builder/cta/high-impact.webp',

                    'background_overlay' => 72,

                    'phone_number' => '+880 1700-000020',

                    'email' => 'high@example.com',

                    'whatsapp_number' => '+880 1700-000021',

                    'whatsapp_message' => 'Hello I would like to discuss a project',
                ],
            );

        $splitConfig =
            array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'split_lead_capture',
                    ),
                [
                    'phone_number' => '+880 1700-000022',

                    'email' => 'split@example.com',

                    'whatsapp_number' => '+880 1700-000023',

                    'whatsapp_message' => 'Hello from Split Lead Capture',

                    'newsletter_placeholder' => 'Your email address',

                    'newsletter_button_label' => 'Join Newsletter',

                    'newsletter_note' => 'Updates only. No spam.',
                ],
            );

        $contactGridConfig =
            array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'contact_grid',
                    ),
                [
                    'phone_number' => '+880 1700-000024',

                    'email' => 'contact@example.com',

                    'whatsapp_number' => '+880 1700-000025',

                    'whatsapp_message' => 'Hello from Contact Grid',
                ],
            );

        $highImpact =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::CallToAction,

                    'template' => 'high_impact',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => $highImpactConfig,
                ]);

        $split =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::CallToAction,

                    'template' => 'split_lead_capture',

                    'position' => 20,

                    'is_enabled' => true,

                    'config' => $splitConfig,
                ]);

        $contactGrid =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::CallToAction,

                    'template' => 'contact_grid',

                    'position' => 30,

                    'is_enabled' => true,

                    'config' => $contactGridConfig,
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
                        3,
                    )
                    ->where(
                        'page.sections.0.id',
                        $highImpact->id,
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
                        'page.sections.0.config.background_type',
                        'image',
                    )
                    ->where(
                        'page.sections.0.config.background_image',
                        '/storage/page-builder/cta/high-impact.webp',
                    )
                    ->where(
                        'page.sections.0.config.background_overlay',
                        72,
                    )
                    ->where(
                        'page.sections.0.config.whatsapp_number',
                        '+880 1700-000021',
                    )
                    ->where(
                        'page.sections.0.config.whatsapp_message',
                        'Hello I would like to discuss a project',
                    )
                    ->where(
                        'page.sections.0.data',
                        [],
                    )
                    ->where(
                        'page.sections.1.id',
                        $split->id,
                    )
                    ->where(
                        'page.sections.1.type',
                        SectionType::CallToAction->value,
                    )
                    ->where(
                        'page.sections.1.template',
                        'split_lead_capture',
                    )
                    ->where(
                        'page.sections.1.config.whatsapp_message',
                        'Hello from Split Lead Capture',
                    )
                    ->where(
                        'page.sections.1.config.newsletter_placeholder',
                        'Your email address',
                    )
                    ->where(
                        'page.sections.1.config.newsletter_button_label',
                        'Join Newsletter',
                    )
                    ->where(
                        'page.sections.1.config.newsletter_note',
                        'Updates only. No spam.',
                    )
                    ->where(
                        'page.sections.1.data',
                        [],
                    )
                    ->where(
                        'page.sections.2.id',
                        $contactGrid->id,
                    )
                    ->where(
                        'page.sections.2.type',
                        SectionType::CallToAction->value,
                    )
                    ->where(
                        'page.sections.2.template',
                        'contact_grid',
                    )
                    ->where(
                        'page.sections.2.config.phone_number',
                        '+880 1700-000024',
                    )
                    ->where(
                        'page.sections.2.config.email',
                        'contact@example.com',
                    )
                    ->where(
                        'page.sections.2.config.whatsapp_number',
                        '+880 1700-000025',
                    )
                    ->where(
                        'page.sections.2.config.whatsapp_message',
                        'Hello from Contact Grid',
                    )
                    ->where(
                        'page.sections.2.data',
                        [],
                    ),
            );
    }

    public function test_disabled_call_to_action_section_is_not_exposed_publicly(): void
    {
        $page = Page::factory()->create([
            'title' => 'Public CTA Page',

            'slug' => 'public-cta-page',

            'status' => PageStatus::Published,

            'content_mode' => PageContentMode::Builder,

            'published_at' => now()->subMinute(),
        ]);

        $definition =
            new CallToActionSection;

        $enabled =
            $page
                ->sections()
                ->create([
                    'type' => SectionType::CallToAction,

                    'template' => 'high_impact',

                    'position' => 10,

                    'is_enabled' => true,

                    'config' => $definition
                        ->defaultConfigForTemplate(
                            'high_impact',
                        ),
                ]);

        $page
            ->sections()
            ->create([
                'type' => SectionType::CallToAction,

                'template' => 'contact_grid',

                'position' => 20,

                'is_enabled' => false,

                'config' => $definition
                    ->defaultConfigForTemplate(
                        'contact_grid',
                    ),
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
                        $enabled->id,
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
                        'page.sections.0.data',
                        [],
                    ),
            );
    }
}
