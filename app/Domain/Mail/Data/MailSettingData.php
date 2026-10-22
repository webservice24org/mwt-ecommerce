<?php

declare(strict_types=1);

namespace App\Domain\Mail\Data;

final readonly class MailSettingData
{
    public function __construct(
        public bool $enabled,
        public string $mailer,
        public ?string $host,
        public ?int $port,
        public ?string $username,
        public ?string $password,
        public ?string $encryption,
        public ?string $fromAddress,
        public ?string $fromName,
        public ?string $adminOrderNotificationEmail,
    ) {}
}
