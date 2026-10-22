<?php

declare(strict_types=1);

namespace App\Domain\Mail\Queries;

use App\Domain\Mail\Data\MailSettingData;
use App\Models\MailSetting;

final class GetMailSettingDataQuery
{
    public function execute(): MailSettingData
    {
        $setting =
            MailSetting::current();

        return new MailSettingData(
            enabled: $setting->enabled,

            mailer: $setting->mailer,

            host: $setting->host,

            port: $setting->port,

            username: $setting->username,

            /*
             * Decrypted only inside trusted
             * backend runtime code.
             */
            password: $setting->password,

            encryption: $setting->encryption,

            fromAddress: $setting->from_address,

            fromName: $setting->from_name,

            adminOrderNotificationEmail: $setting
                ->admin_order_notification_email,
        );
    }
}
