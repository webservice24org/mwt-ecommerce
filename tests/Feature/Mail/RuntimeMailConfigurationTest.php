<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Actions\ConfigureRuntimeMailAction;
use App\Models\MailSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RuntimeMailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_database_settings_are_applied_to_runtime_mail_config(): void
    {
        MailSetting::current()
            ->update([
                'enabled' => true,

                'mailer' => 'smtp',

                'host' => 'smtp.example.com',

                'port' => 587,

                'username' => 'mailer-user',

                'password' => 'secret-password',

                'encryption' => 'tls',

                'from_address' => 'orders@example.com',

                'from_name' => 'Example Store',
            ]);

        app(
            ConfigureRuntimeMailAction::class,
        )->execute();

        $this->assertSame(
            'smtp',
            config(
                'mail.default',
            ),
        );

        $this->assertSame(
            'smtp.example.com',
            config(
                'mail.mailers.smtp.host',
            ),
        );

        $this->assertSame(
            587,
            config(
                'mail.mailers.smtp.port',
            ),
        );

        $this->assertSame(
            'mailer-user',
            config(
                'mail.mailers.smtp.username',
            ),
        );

        $this->assertSame(
            'secret-password',
            config(
                'mail.mailers.smtp.password',
            ),
        );

        $this->assertSame(
            'orders@example.com',
            config(
                'mail.from.address',
            ),
        );

        $this->assertSame(
            'Example Store',
            config(
                'mail.from.name',
            ),
        );
    }

    public function test_ssl_maps_to_smtps_scheme(): void
    {
        MailSetting::current()
            ->update([
                'enabled' => true,

                'encryption' => 'ssl',
            ]);

        app(
            ConfigureRuntimeMailAction::class,
        )->execute();

        $this->assertSame(
            'smtps',
            config(
                'mail.mailers.smtp.scheme',
            ),
        );
    }

    public function test_disabled_settings_do_not_override_runtime_mail_config(): void
    {
        config([
            'mail.default' => 'log',

            'mail.mailers.smtp.host' => 'env-host.example.com',
        ]);

        MailSetting::current()
            ->update([
                'enabled' => false,

                'host' => 'database-host.example.com',
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
            'env-host.example.com',
            config(
                'mail.mailers.smtp.host',
            ),
        );
    }
}
