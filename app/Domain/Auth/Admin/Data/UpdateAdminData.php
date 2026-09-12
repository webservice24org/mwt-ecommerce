<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Data;

use App\Domain\Auth\Admin\Enums\AdminRole;

final readonly class UpdateAdminData
{
    public function __construct(
        public string $name,
        public string $email,
        public AdminRole $role,
        public bool $isActive,
        public ?string $password,
    ) {}
}
