<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Mail\TestMail;
use App\Models\Admin;
use App\Models\MailSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class MailSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_mail_settings(): void
    {
        $this
            ->get(
                route(
                    'admin.website-settings.mail.edit',
                ),
            )
            ->assertRedirect(
                route(
                    'admin.login',
                ),
            );
    }

    public function test_active_admin_can_view_mail_settings(): void
    {
        $admin =
            $this->createAdmin();

        MailSetting::current()
            ->update([
                'host' => 'smtp.example.com',

                'port' => 587,

                'username' => 'mailer',

                'password' => 'secret',

                'encryption' => 'tls',
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.website-settings.mail.edit',
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->component(
                        'Admin/WebsiteSettings/Mail/Edit',
                    )
                    ->where(
                        'setting.host',
                        'smtp.example.com',
                    )
                    ->where(
                        'setting.port',
                        587,
                    )
                    ->where(
                        'setting.username',
                        'mailer',
                    )
                    ->where(
                        'setting.has_password',
                        true,
                    )
                    ->missing(
                        'setting.password',
                    )
                    ->where(
                        'update_url',
                        route(
                            'admin.website-settings.mail.update',
                        ),
                    ),
            );
    }

    public function test_admin_can_update_mail_settings(): void
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
                    'admin.website-settings.mail.update',
                ),
                $this->validPayload(),
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(
                route(
                    'admin.website-settings.mail.edit',
                ),
            );

        $setting =
            MailSetting::current();

        $this->assertTrue(
            $setting->enabled,
        );

        $this->assertSame(
            'smtp',
            $setting->mailer,
        );

        $this->assertSame(
            'sandbox.smtp.mailtrap.io',
            $setting->host,
        );

        $this->assertSame(
            587,
            $setting->port,
        );

        $this->assertSame(
            'mail-user',
            $setting->username,
        );

        $this->assertSame(
            'new-secret-password',
            $setting->password,
        );

        $this->assertSame(
            'tls',
            $setting->encryption,
        );

        $this->assertSame(
            'orders@example.com',
            $setting->from_address,
        );

        $this->assertSame(
            'MWT Ecommerce',
            $setting->from_name,
        );

        $this->assertSame(
            'admin@example.com',
            $setting
                ->admin_order_notification_email,
        );
    }

    public function test_blank_password_keeps_existing_secret(): void
    {
        $admin =
            $this->createAdmin();

        $setting =
            MailSetting::current();

        $setting->update([
            'password' => 'existing-secret',
        ]);

        $payload =
            $this->validPayload();

        $payload['password'] =
            '';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'existing-secret',
            $setting
                ->fresh()
                ->password,
        );
    }

    public function test_new_password_replaces_existing_secret(): void
    {
        $admin =
            $this->createAdmin();

        $setting =
            MailSetting::current();

        $setting->update([
            'password' => 'old-secret',
        ]);

        $payload =
            $this->validPayload();

        $payload['password'] =
            'replacement-secret';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'replacement-secret',
            $setting
                ->fresh()
                ->password,
        );
    }

    public function test_replaced_password_remains_encrypted_at_rest(): void
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
                    'admin.website-settings.mail.update',
                ),
                $this->validPayload(),
            )
            ->assertSessionHasNoErrors();

        $raw =
            DB::table(
                'mail_settings',
            )
                ->value(
                    'password',
                );

        $this->assertIsString(
            $raw,
        );

        $this->assertNotSame(
            'new-secret-password',
            $raw,
        );

        $this->assertStringNotContainsString(
            'new-secret-password',
            $raw,
        );
    }

    public function test_password_is_never_returned_to_inertia(): void
    {
        $admin =
            $this->createAdmin();

        MailSetting::current()
            ->update([
                'password' => 'never-return-this',
            ]);

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.website-settings.mail.edit',
                    ),
                );

        $response
            ->assertInertia(
                fn (
                    AssertableInertia $page,
                ) => $page
                    ->where(
                        'setting.has_password',
                        true,
                    )
                    ->missing(
                        'setting.password',
                    ),
            );

        $this->assertStringNotContainsString(
            'never-return-this',
            $response->getContent(),
        );
    }

    public function test_invalid_port_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        $payload =
            $this->validPayload();

        $payload['port'] =
            70000;

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'port',
            );
    }

    public function test_invalid_encryption_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        $payload =
            $this->validPayload();

        $payload['encryption'] =
            'something-unsafe';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'encryption',
            );
    }

    public function test_invalid_sender_email_is_rejected(): void
    {
        $admin =
            $this->createAdmin();

        $payload =
            $this->validPayload();

        $payload['from_address'] =
            'invalid-email';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.update',
                ),
                $payload,
            )
            ->assertSessionHasErrors(
                'from_address',
            );
    }

    public function test_optional_strings_are_trimmed(): void
    {
        $admin =
            $this->createAdmin();

        $payload =
            $this->validPayload();

        $payload['host'] =
            '  smtp.example.com  ';

        $payload['from_name'] =
            '  Example Store  ';

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.update',
                ),
                $payload,
            )
            ->assertSessionHasNoErrors();

        $setting =
            MailSetting::current();

        $this->assertSame(
            'smtp.example.com',
            $setting->host,
        );

        $this->assertSame(
            'Example Store',
            $setting->from_name,
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
        return array_replace(
            [
                'enabled' => true,

                'mailer' => 'smtp',

                'host' => 'sandbox.smtp.mailtrap.io',

                'port' => 587,

                'username' => 'mail-user',

                'password' => 'new-secret-password',

                'encryption' => 'tls',

                'from_address' => 'orders@example.com',

                'from_name' => 'MWT Ecommerce',

                'admin_order_notification_email' => 'admin@example.com',
            ],
            $overrides,
        );
    }

    public function test_admin_can_send_test_email_using_saved_configuration(): void
    {
        Mail::fake();

        $admin =
            $this->createAdmin();

        MailSetting::current()
            ->update([
                'enabled' => true,

                'mailer' => 'smtp',

                'host' => 'smtp.example.com',

                'port' => 587,

                'username' => 'mailer',

                'password' => 'secret',

                'encryption' => 'tls',

                'from_address' => 'orders@example.com',

                'from_name' => 'Example Store',
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.test',
                ),
                [
                    'email' => 'recipient@example.com',
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success',
            );

        Mail::assertSent(
            TestMail::class,
            static fn (
                TestMail $mail,
            ): bool => $mail->hasTo(
                'recipient@example.com',
            ),
        );
    }

    public function test_test_email_is_rejected_when_mail_is_disabled(): void
    {
        Mail::fake();

        $admin =
            $this->createAdmin();

        MailSetting::current()
            ->update([
                'enabled' => false,
            ]);

        $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route(
                    'admin.website-settings.mail.test',
                ),
                [
                    'email' => 'recipient@example.com',
                ],
            )
            ->assertSessionHasErrors(
                'mail',
            );

        Mail::assertNothingSent();
    }

    public function test_test_email_requires_valid_recipient(): void
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
                    'admin.website-settings.mail.test',
                ),
                [
                    'email' => 'not-an-email',
                ],
            )
            ->assertSessionHasErrors(
                'email',
            );
    }

    public function test_mail_settings_page_never_exposes_saved_password(): void
    {
        $admin =
            $this->createAdmin();

        MailSetting::current()
            ->update([
                'password' => 'never-return-this-secret',
            ]);

        $response =
            $this
                ->actingAs(
                    $admin,
                    'admin',
                )
                ->get(
                    route(
                        'admin.website-settings.mail.edit',
                    ),
                );

        $response
            ->assertOk()
            ->assertDontSee(
                'never-return-this-secret',
            );
    }
}
