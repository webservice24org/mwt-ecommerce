<?php

declare(strict_types=1);

namespace App\Domain\Promotion\Support;

final class PromoCodeNormalizer
{
    public static function normalize(string $code): string
    {
        return mb_strtoupper(
            trim($code),
        );
    }
}
