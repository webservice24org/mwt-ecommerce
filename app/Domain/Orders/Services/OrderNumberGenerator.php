<?php

declare(strict_types=1);

namespace App\Domain\Orders\Services;

use Illuminate\Support\Str;

final class OrderNumberGenerator
{
    public function generate(): string
    {
        return 'ORD-'.Str::ulid();
    }
}
