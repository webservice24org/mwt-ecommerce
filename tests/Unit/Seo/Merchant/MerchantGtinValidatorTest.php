<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Services\MerchantGtinValidator;
use PHPUnit\Framework\TestCase;

final class MerchantGtinValidatorTest extends TestCase
{
    public function test_valid_gtin_lengths_and_check_digits_are_accepted(): void
    {
        $validator =
            new MerchantGtinValidator;

        foreach (
            [
                '96385074',
                '012345678905',
                '4006381333931',
                '10012345000017',
            ] as $gtin
        ) {
            self::assertTrue(
                $validator->valid(
                    $gtin,
                ),
                $gtin,
            );
        }
    }

    public function test_invalid_or_guessed_values_are_rejected(): void
    {
        $validator =
            new MerchantGtinValidator;

        foreach (
            [
                null,
                '',
                'ABC123',
                '4006381333932',
                '1234567',
                '123456789012345',
            ] as $gtin
        ) {
            self::assertFalse(
                $validator->valid(
                    $gtin,
                ),
                (string) $gtin,
            );
        }
    }

    public function test_whitespace_is_ignored_but_punctuation_is_not(): void
    {
        $validator =
            new MerchantGtinValidator;

        self::assertTrue(
            $validator->valid(
                '4006 3813 3393 1',
            ),
        );

        self::assertFalse(
            $validator->valid(
                '4006-3813-3393-1',
            ),
        );
    }
}
