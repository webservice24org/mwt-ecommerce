<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\PageBuilder\Enums\SectionType;
use App\Domain\PageBuilder\Sections\CallToActionSection;
use App\Models\Admin;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CallToActionSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_all_call_to_action_templates(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page = Page::factory()->create();

        $definition =
            new CallToActionSection;

        $templates = [
            'high_impact' => array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'high_impact',
                    ),
                [
                    'phone_number' => '+880 1700-000001',

                    'email' => 'high-impact@example.com',

                    'whatsapp_number' => '+880 1700-000002',

                    'whatsapp_message' => 'Hello from High Impact CTA.',
                ],
            ),

            'split_lead_capture' => array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'split_lead_capture',
                    ),
                [
                    'phone_number' => '+880 1700-000003',

                    'email' => 'split@example.com',

                    'whatsapp_number' => '+880 1700-000004',

                    'whatsapp_message' => 'Hello from Split Lead Capture CTA.',
                ],
            ),

            'contact_grid' => array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'contact_grid',
                    ),
                [
                    'phone_number' => '+880 1700-000005',

                    'email' => 'contact@example.com',

                    'whatsapp_number' => '+880 1700-000006',

                    'whatsapp_message' => 'Hello from Contact Grid CTA.',
                ],
            ),
        ];

        foreach (
            $templates as $template => $config
        ) {
            $response = $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->post(
                    route(
                        'admin.pages.sections.store',
                        $page,
                    ),
                    [
                        'type' => SectionType::CallToAction->value,

                        'template' => $template,

                        'config' => $config,

                        'is_enabled' => true,
                    ],
                );

            $response
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        $sections =
            PageSection::query()
                ->where(
                    'page_id',
                    $page->id,
                )
                ->where(
                    'type',
                    SectionType::CallToAction->value,
                )
                ->orderBy(
                    'position',
                )
                ->get();

        $this->assertCount(
            3,
            $sections,
        );

        $this->assertSame(
            [
                'high_impact',
                'split_lead_capture',
                'contact_grid',
            ],
            $sections
                ->pluck('template')
                ->all(),
        );

        $this->assertSame(
            [
                10,
                20,
                30,
            ],
            $sections
                ->pluck('position')
                ->all(),
        );

        foreach ($sections as $section) {
            $this->assertSame(
                $templates[
                    $section->template
                ],
                $section->config,
            );

            $this->assertTrue(
                $section->is_enabled,
            );
        }
    }

    public function test_editor_can_change_call_to_action_template_and_update_normalized_config(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page =
            Page::factory()->create();

        $definition =
            new CallToActionSection;

        $section =
            PageSection::factory()
                ->for($page)
                ->create([
                    'type' => SectionType::CallToAction,

                    'template' => 'high_impact',

                    'config' => $definition
                        ->defaultConfigForTemplate(
                            'high_impact',
                        ),

                    'is_enabled' => true,
                ]);

        $submittedConfig = [
            'eyebrow' => '  Project updates  ',

            'heading' => '  Let us discuss your next project  ',

            'description' => '  Subscribe or contact our team directly.  ',

            'background_type' => 'image',

            'background_color' => '  #FFFFFF  ',

            'background_image' => '  /storage/page-builder/cta/split.webp  ',

            'background_overlay' => 55,

            'text_theme' => 'dark',

            'phone_label' => '  Call our team  ',

            'phone_number' => '  +880 1700-000010  ',

            'email_label' => '  Email our team  ',

            'email' => '  projects@example.com  ',

            'whatsapp_label' => '  WhatsApp us  ',

            'whatsapp_number' => '  +880 1700-000011  ',

            'whatsapp_message' => '  Hello, I would like to discuss a Laravel ecommerce project.  ',

            'newsletter_placeholder' => '  Enter your email  ',

            'newsletter_button_label' => '  Join Updates  ',

            'newsletter_note' => '  No spam. Unsubscribe anytime.  ',
        ];

        $expectedConfig =
            $definition
                ->configSchema()
                ->validate(
                    $submittedConfig,
                );

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,

                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => SectionType::CallToAction->value,

                    'template' => 'split_lead_capture',

                    'config' => $submittedConfig,

                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $section->refresh();

        $this->assertSame(
            SectionType::CallToAction,
            $section->type,
        );

        $this->assertSame(
            'split_lead_capture',
            $section->template,
        );

        $this->assertSame(
            $expectedConfig,
            $section->config,
        );

        $this->assertSame(
            'Hello, I would like to discuss a Laravel ecommerce project.',
            $section->config[
                'whatsapp_message'
            ],
        );

        $this->assertTrue(
            $section->is_enabled,
        );
    }

    public function test_whatsapp_message_preserves_spaces_between_words(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page =
            Page::factory()->create();

        $definition =
            new CallToActionSection;

        $config =
            array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'high_impact',
                    ),
                [
                    'whatsapp_number' => '+880 1700-000020',

                    'whatsapp_message' => 'Hello I would like to discuss my ecommerce project',
                ],
            );

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.pages.sections.store',
                    $page,
                ),
                [
                    'type' => SectionType::CallToAction->value,

                    'template' => 'high_impact',

                    'config' => $config,

                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $section =
            PageSection::query()
                ->where(
                    'page_id',
                    $page->id,
                )
                ->where(
                    'type',
                    SectionType::CallToAction->value,
                )
                ->firstOrFail();

        $this->assertSame(
            'Hello I would like to discuss my ecommerce project',
            $section->config[
                'whatsapp_message'
            ],
        );
    }

    public function test_unsafe_background_update_is_rejected_and_existing_config_is_preserved(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page =
            Page::factory()->create();

        $definition =
            new CallToActionSection;

        $originalConfig =
            $definition
                ->defaultConfigForTemplate(
                    'high_impact',
                );

        $section =
            PageSection::factory()
                ->for($page)
                ->create([
                    'type' => SectionType::CallToAction,

                    'template' => 'high_impact',

                    'config' => $originalConfig,

                    'is_enabled' => true,
                ]);

        $unsafeConfig =
            array_replace(
                $definition
                    ->defaultConfigForTemplate(
                        'contact_grid',
                    ),
                [
                    'background_type' => 'image',

                    'background_image' => 'javascript:alert(1)',
                ],
            );

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->put(
                route(
                    'admin.pages.sections.update',
                    [
                        'page' => $page,

                        'section' => $section->id,
                    ],
                ),
                [
                    'type' => SectionType::CallToAction->value,

                    'template' => 'contact_grid',

                    'config' => $unsafeConfig,

                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->assertSessionHasErrors();

        $section->refresh();

        $this->assertSame(
            'high_impact',
            $section->template,
        );

        $this->assertSame(
            $originalConfig,
            $section->config,
        );
    }

    public function test_call_to_action_http_boundary_rejects_unknown_configuration_fields(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $page =
            Page::factory()->create();

        $definition =
            new CallToActionSection;

        $config =
            $definition
                ->defaultConfigForTemplate(
                    'contact_grid',
                );

        $config['unsafe_html'] =
            '<script>alert(1)</script>';

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->post(
                route(
                    'admin.pages.sections.store',
                    $page,
                ),
                [
                    'type' => SectionType::CallToAction->value,

                    'template' => 'contact_grid',

                    'config' => $config,

                    'is_enabled' => true,
                ],
            );

        $response
            ->assertRedirect(
                route(
                    'admin.pages.builder',
                    $page,
                ),
            )
            ->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'page_sections',
            0,
        );
    }
}
