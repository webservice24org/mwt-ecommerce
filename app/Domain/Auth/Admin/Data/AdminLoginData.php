<?php

declare(strict_types=1);

namespace App\Domain\Auth\Admin\Data;

final readonly class AdminLoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember,
    ) {}
}
