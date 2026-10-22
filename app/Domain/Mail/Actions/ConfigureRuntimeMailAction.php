<?php

declare(strict_types=1);

namespace App\Domain\Mail\Actions;

use App\Domain\Mail\Data\MailSettingData;
use App\Domain\Mail\Queries\GetMailSettingDataQuery;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

final readonly class ConfigureRuntimeMailAction
{
    public function __construct(
        private GetMailSettingDataQuery $query,
    ) {}

    public function execute(): MailSettingData
    {
        $settings =
            $this->query->execute();

        if (! $settings->enabled) {
            return $settings;
        }

        Config::set(
            'mail.default',
            $settings->mailer,
        );

        Config::set(
            'mail.mailers.smtp.transport',
            'smtp',
        );

        Config::set(
            'mail.mailers.smtp.scheme',
            $this->scheme(
                $settings->encryption,
            ),
        );

        Config::set(
            'mail.mailers.smtp.url',
            null,
        );

        Config::set(
            'mail.mailers.smtp.host',
            $settings->host,
        );

        Config::set(
            'mail.mailers.smtp.port',
            $settings->port,
        );

        Config::set(
            'mail.mailers.smtp.username',
            $settings->username,
        );

        Config::set(
            'mail.mailers.smtp.password',
            $settings->password,
        );

        Config::set(
            'mail.from.address',
            $settings->fromAddress,
        );

        Config::set(
            'mail.from.name',
            $settings->fromName,
        );

        /*
         * MailManager caches resolved mailers.
         *
         * Purge the configured mailer after
         * changing its runtime configuration so
         * Laravel creates a fresh transport using
         * the database values.
         */
        Mail::purge(
            $settings->mailer,
        );

        return $settings;
    }

    private function scheme(
        ?string $encryption,
    ): ?string {
        return match ($encryption) {
            'ssl' => 'smtps',

            'tls',
            null => null,

            default => null,
        };
    }
}
