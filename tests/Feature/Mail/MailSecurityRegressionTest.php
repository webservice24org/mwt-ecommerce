<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Actions\ConfigureRuntimeMailAction;
use App\Domain\Mail\Queries\GetMailSettingEditorDataQuery;
use App\Mail\Orders\NewOrderAdminMail;
use App\Mail\Orders\OrderPlacedCustomerMail;
use App\Mail\Orders\OrderStatusChangedMail;
use App\Models\MailSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class MailSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_smtp_password_is_encrypted_at_rest(): void
    {
        $setting =
            MailSetting::current();

        $setting->update([
            'password' => 'super-secret-smtp-password',
        ]);

        $this->assertSame(
            'super-secret-smtp-password',
            $setting
                ->fresh()
                ->password,
        );

        $this->assertDatabaseMissing(
            'mail_settings',
            [
                'password' => 'super-secret-smtp-password',
            ],
        );
    }

    public function test_editor_data_never_contains_smtp_password(): void
    {
        MailSetting::current()
            ->update([
                'password' => 'super-secret-smtp-password',
            ]);

        $data =
            app(
                GetMailSettingEditorDataQuery::class,
            )->execute();

        $this->assertArrayNotHasKey(
            'password',
            $data,
        );

        $this->assertTrue(
            $data['has_password'],
        );
    }

    public function test_editor_data_does_not_contain_decrypted_password_value_anywhere(): void
    {
        MailSetting::current()
            ->update([
                'password' => 'super-secret-smtp-password',
            ]);

        $data =
            app(
                GetMailSettingEditorDataQuery::class,
            )->execute();

        $serialized =
            json_encode(
                $data,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'super-secret-smtp-password',
            $serialized,
        );
    }

    public function test_runtime_mail_configuration_uses_decrypted_password_only_in_runtime_config(): void
    {
        MailSetting::current()
            ->update([
                'enabled' => true,

                'mailer' => 'smtp',

                'host' => 'smtp.example.com',

                'port' => 587,

                'username' => 'mailer-user',

                'password' => 'runtime-secret',

                'encryption' => 'tls',

                'from_address' => 'store@example.com',

                'from_name' => 'MWT Ecommerce',
            ]);

        app(
            ConfigureRuntimeMailAction::class,
        )->execute();

        $this->assertSame(
            'runtime-secret',
            config(
                'mail.mailers.smtp.password',
            ),
        );

        $this->assertDatabaseMissing(
            'mail_settings',
            [
                'password' => 'runtime-secret',
            ],
        );
    }

    public function test_disabled_database_mail_does_not_replace_environment_mail_configuration(): void
    {
        config([
            'mail.default' => 'log',

            'mail.mailers.smtp.host' => 'environment.example.com',
        ]);

        MailSetting::current()
            ->update([
                'enabled' => false,

                'mailer' => 'smtp',

                'host' => 'database.example.com',
            ]);

        app(
            ConfigureRuntimeMailAction::class,
        )->execute();

        $this->assertSame(
            'log',
            config(
                'mail.default',
            ),
        );

        $this->assertSame(
            'environment.example.com',
            config(
                'mail.mailers.smtp.host',
            ),
        );
    }

    public function test_order_mailables_remain_queue_ready(): void
    {
        $this->assertTrue(
            is_subclass_of(
                OrderPlacedCustomerMail::class,
                ShouldQueue::class,
            ),
        );

        $this->assertTrue(
            is_subclass_of(
                NewOrderAdminMail::class,
                ShouldQueue::class,
            ),
        );

        $this->assertTrue(
            is_subclass_of(
                OrderStatusChangedMail::class,
                ShouldQueue::class,
            ),
        );
    }

    public function test_mail_fake_starts_with_no_unexpected_queued_mail(): void
    {
        Mail::fake();

        Mail::assertNothingQueued();
    }
}
