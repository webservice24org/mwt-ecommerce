<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Data;

final readonly class SearchConsolePropertyAccessData
{
    public function __construct(
        public string $siteUrl,
        public string $permissionLevel,
    ) {}

    public function isVerified(): bool
    {
        return in_array($this->permissionLevel, [
            'siteOwner',
            'siteFullUser',
            'siteRestrictedUser',
        ], true);
    }
}
