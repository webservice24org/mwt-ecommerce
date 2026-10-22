<?php

declare(strict_types=1);

namespace App\Domain\Mail\Queries;

use App\Models\MailSetting;

final class GetMailSettingEditorDataQuery
{
    /**
     * @return array{
     *     enabled: bool,
     *     mailer: string,
     *     host: string|null,
     *     port: int|null,
     *     username: string|null,
     *     has_password: bool,
     *     encryption: string|null,
     *     from_address: string|null,
     *     from_name: string|null,
     *     admin_order_notification_email: string|null
     * }
     */
    public function execute(): array
    {
        $setting =
            MailSetting::current();

        return [
            'enabled' => $setting->enabled,

            'mailer' => $setting->mailer,

            'host' => $setting->host,

            'port' => $setting->port,

            'username' => $setting->username,

            /*
             * Never expose the decrypted
             * credential itself.
             */
            'has_password' => filled(
                $setting->password,
            ),

            'encryption' => $setting->encryption,

            'from_address' => $setting->from_address,

            'from_name' => $setting->from_name,

            'admin_order_notification_email' => $setting
                ->admin_order_notification_email,
        ];
    }
}
