<?php

declare(strict_types=1);

namespace App\Domain\Auth\Customer\Data;

final readonly class LoginCustomerData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember,
    ) {}
}
