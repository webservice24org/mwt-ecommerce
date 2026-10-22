<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\InvoiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class InvoiceSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_cannot_view_invoice_settings(): void
    {
        $this
            ->get(
                route(
                    'admin.website-settings.invoice.edit',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_active_admin_can_view_invoice_settings(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.website-settings.invoice.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component(
                        'Admin/WebsiteSettings/Invoice/Edit',
                    )
                    ->where(
                        'setting.company_name',
                        'MWT Ecommerce',
                    )
                    ->where(
                        'setting.company_tagline',
                        'Enterprise Store',
                    )
                    ->where(
                        'setting.header_start_color',
                        '#4338ca',
                    )
                    ->where(
                        'setting.header_end_color',
                        '#2563eb',
                    )
                    ->where(
                        'setting.footer_color',
                        '#0f172a',
                    )
                    ->where(
                        'setting.footer_credit',
                        'Thank you for your business!',
                    )
                    ->has(
                        'update_url',
                    ),
            );
    }

    public function test_admin_can_update_invoice_branding_settings(): void
    {
        $admin =
            $this->createAdmin();

        $editUrl =
            route(
                'admin.website-settings.invoice.edit',
            );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->from(
                $editUrl,
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'company_name' => 'MicroWeb Technology Ltd',

                    'company_tagline' => 'Digital Commerce',

                    'header_start_color' => '#112233',

                    'header_end_color' => '#445566',

                    'footer_color' => '#0a0b0c',

                    'support_email' => 'invoice@example.com',

                    'footer_credit' => 'Powered by MicroWeb Technology Ltd.',
                ]),
            )
            ->assertRedirect(
                $editUrl,
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'invoice_settings',
            [
                'company_name' => 'MicroWeb Technology Ltd',

                'company_tagline' => 'Digital Commerce',

                'header_start_color' => '#112233',

                'header_end_color' => '#445566',

                'footer_color' => '#0a0b0c',

                'support_email' => 'invoice@example.com',

                'footer_credit' => 'Powered by MicroWeb Technology Ltd.',
            ],
        );
    }

    public function test_invoice_setting_text_values_are_trimmed(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'company_name' => '  MicroWeb Commerce  ',

                    'company_tagline' => '  Enterprise Store  ',

                    'support_email' => '  billing@example.com  ',

                    'footer_credit' => '  Thank you for your business.  ',
                ]),
            )
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'invoice_settings',
            [
                'company_name' => 'MicroWeb Commerce',

                'company_tagline' => 'Enterprise Store',

                'support_email' => 'billing@example.com',

                'footer_credit' => 'Thank you for your business.',
            ],
        );
    }

    public function test_invoice_colors_must_be_valid_six_digit_hex_values(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'header_start_color' => 'red',

                    'header_end_color' => '#123',

                    'footer_color' => '#GGGGGG',
                ]),
            )
            ->assertSessionHasErrors([
                'header_start_color',
                'header_end_color',
                'footer_color',
            ]);
    }

    public function test_support_email_must_be_valid_when_provided(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'support_email' => 'not-an-email',
                ]),
            )
            ->assertSessionHasErrors(
                'support_email',
            );
    }

    public function test_admin_can_upload_png_invoice_logo(): void
    {
        Storage::fake(
            'public',
        );

        $admin =
            $this->createAdmin();

        $logo =
            UploadedFile::fake()
                ->image(
                    'invoice-logo.png',
                    600,
                    200,
                )
                ->size(
                    500,
                );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'logo' => $logo,
                ]),
            )
            ->assertSessionHasNoErrors();

        $setting =
            InvoiceSetting::query()
                ->firstOrFail();

        $this->assertNotNull(
            $setting->logo_path,
        );

        $this->assertStringStartsWith(
            'invoice-branding/',
            $setting->logo_path,
        );

        Storage::disk(
            'public',
        )->assertExists(
            $setting->logo_path,
        );
    }

    public function test_svg_invoice_logo_is_rejected(): void
    {
        Storage::fake(
            'public',
        );

        $admin =
            $this->createAdmin();

        $svg =
            UploadedFile::fake()
                ->create(
                    'invoice-logo.svg',
                    10,
                    'image/svg+xml',
                );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'logo' => $svg,
                ]),
            )
            ->assertSessionHasErrors(
                'logo',
            );
    }

    public function test_oversized_invoice_logo_is_rejected(): void
    {
        Storage::fake(
            'public',
        );

        $admin =
            $this->createAdmin();

        $logo =
            UploadedFile::fake()
                ->image(
                    'large-logo.png',
                    600,
                    200,
                )
                ->size(
                    2049,
                );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'logo' => $logo,
                ]),
            )
            ->assertSessionHasErrors(
                'logo',
            );
    }

    public function test_replacing_invoice_logo_deletes_old_file(): void
    {
        Storage::fake(
            'public',
        );

        $admin =
            $this->createAdmin();

        $setting =
            InvoiceSetting::current();

        $oldPath =
            'invoice-branding/old-logo.png';

        Storage::disk(
            'public',
        )->put(
            $oldPath,
            'old-logo',
        );

        $setting->update([
            'logo_path' => $oldPath,
        ]);

        $newLogo =
            UploadedFile::fake()
                ->image(
                    'new-logo.png',
                    600,
                    200,
                )
                ->size(
                    400,
                );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'logo' => $newLogo,
                ]),
            )
            ->assertSessionHasNoErrors();

        $setting->refresh();

        Storage::disk(
            'public',
        )->assertMissing(
            $oldPath,
        );

        $this->assertNotNull(
            $setting->logo_path,
        );

        $this->assertNotSame(
            $oldPath,
            $setting->logo_path,
        );

        Storage::disk(
            'public',
        )->assertExists(
            $setting->logo_path,
        );
    }

    public function test_admin_can_remove_existing_invoice_logo(): void
    {
        Storage::fake(
            'public',
        );

        $admin =
            $this->createAdmin();

        $setting =
            InvoiceSetting::current();

        $oldPath =
            'invoice-branding/remove-me.png';

        Storage::disk(
            'public',
        )->put(
            $oldPath,
            'old-logo',
        );

        $setting->update([
            'logo_path' => $oldPath,
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'remove_logo' => true,
                ]),
            )
            ->assertSessionHasNoErrors();

        $setting->refresh();

        $this->assertNull(
            $setting->logo_path,
        );

        Storage::disk(
            'public',
        )->assertMissing(
            $oldPath,
        );
    }

    public function test_uploading_new_logo_takes_precedence_over_remove_logo_flag(): void
    {
        Storage::fake(
            'public',
        );

        $admin =
            $this->createAdmin();

        $setting =
            InvoiceSetting::current();

        $oldPath =
            'invoice-branding/original.png';

        Storage::disk(
            'public',
        )->put(
            $oldPath,
            'original-logo',
        );

        $setting->update([
            'logo_path' => $oldPath,
        ]);

        $newLogo =
            UploadedFile::fake()
                ->image(
                    'replacement.png',
                    600,
                    200,
                );

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'remove_logo' => true,

                    'logo' => $newLogo,
                ]),
            )
            ->assertSessionHasNoErrors();

        $setting->refresh();

        $this->assertNotNull(
            $setting->logo_path,
        );

        $this->assertNotSame(
            $oldPath,
            $setting->logo_path,
        );

        Storage::disk(
            'public',
        )->assertMissing(
            $oldPath,
        );

        Storage::disk(
            'public',
        )->assertExists(
            $setting->logo_path,
        );
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()
            ->create([
                'role' => AdminRole::Admin,

                'is_active' => true,
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        array $overrides = [],
    ): array {
        return array_merge(
            [
                'company_name' => 'MWT Ecommerce',

                'company_tagline' => 'Enterprise Store',

                'header_start_color' => '#4338ca',

                'header_end_color' => '#2563eb',

                'footer_color' => '#0f172a',

                'support_email' => 'support@example.com',

                'footer_credit' => 'Thank you for your business!',

                'remove_logo' => false,
            ],
            $overrides,
        );
    }

    public function test_removing_support_email_is_allowed(): void
    {
        $admin =
            $this->createAdmin();

        $setting =
            InvoiceSetting::current();

        $setting->update([
            'support_email' => 'billing@example.com',
        ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'support_email' => '',
                ]),
            )
            ->assertSessionHasNoErrors();

        $this->assertNull(
            $setting
                ->fresh()
                ->support_email,
        );
    }

    public function test_invoice_footer_credit_cannot_be_empty(): void
    {
        $admin =
            $this->createAdmin();

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.invoice.update',
                ),
                $this->validPayload([
                    'footer_credit' => '',
                ]),
            )
            ->assertSessionHasErrors(
                'footer_credit',
            );
    }
}
