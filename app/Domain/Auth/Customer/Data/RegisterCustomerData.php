<?php

declare(strict_types=1);

namespace App\Domain\Auth\Customer\Data;

final readonly class RegisterCustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
