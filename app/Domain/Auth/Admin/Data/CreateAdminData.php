<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Data;

use App\Domain\Auth\Admin\Enums\AdminRole;

final readonly class CreateAdminData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public AdminRole $role,
        public bool $isActive,
    ) {}
}
