<?php

declare(strict_types=1);

namespace App\Domain\Customer\Data;

final readonly class CustomerData
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }
}