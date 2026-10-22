<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Domain\Mail\Queries\GetMailSettingDataQuery;
use App\Domain\Mail\Queries\GetMailSettingEditorDataQuery;
use App\Models\MailSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MailSettingDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_mail_setting_is_created_disabled(): void
    {
        $setting =
            MailSetting::current();

        $this->assertSame(
            'smtp',
            $setting->mailer,
        );

        $this->assertFalse(
            $setting->enabled,
        );
    }

    public function test_smtp_password_is_encrypted_at_rest(): void
    {
        $setting =
            MailSetting::current();

        $setting->update([
            'password' => 'very-secret-password',
        ]);

        $setting->refresh();

        /*
         * Eloquent decrypts it for trusted
         * application code.
         */
        $this->assertSame(
            'very-secret-password',
            $setting->password,
        );

        /*
         * Raw DB value must not equal plaintext.
         */
        $rawPassword =
            DB::table(
                'mail_settings',
            )
                ->where(
                    'id',
                    $setting->id,
                )
                ->value(
                    'password',
                );

        $this->assertIsString(
            $rawPassword,
        );

        $this->assertNotSame(
            'very-secret-password',
            $rawPassword,
        );

        $this->assertStringNotContainsString(
            'very-secret-password',
            $rawPassword,
        );
    }

    public function test_runtime_query_can_access_decrypted_password(): void
    {
        $setting =
            MailSetting::current();

        $setting->update([
            'password' => 'runtime-secret',
        ]);

        $data = app(
            GetMailSettingDataQuery::class,
        )->execute();

        $this->assertSame(
            'runtime-secret',
            $data->password,
        );
    }

    public function test_editor_query_never_exposes_password(): void
    {
        $setting =
            MailSetting::current();

        $setting->update([
            'password' => 'browser-must-never-see-this',
        ]);

        $data = app(
            GetMailSettingEditorDataQuery::class,
        )->execute();

        $this->assertTrue(
            $data['has_password'],
        );

        $this->assertArrayNotHasKey(
            'password',
            $data,
        );

        $serialized =
            json_encode(
                $data,
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'browser-must-never-see-this',
            $serialized,
        );
    }

    public function test_editor_query_reports_missing_password_without_exposing_field(): void
    {
        $data = app(
            GetMailSettingEditorDataQuery::class,
        )->execute();

        $this->assertFalse(
            $data['has_password'],
        );

        $this->assertArrayNotHasKey(
            'password',
            $data,
        );
    }

    public function test_mail_setting_casts_port_and_enabled(): void
    {
        $setting =
            MailSetting::query()
                ->create([
                    'mailer' => 'smtp',

                    'port' => 587,

                    'enabled' => true,
                ]);

        $this->assertIsInt(
            $setting->port,
        );

        $this->assertTrue(
            $setting->enabled,
        );
    }
}
