<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Support;

use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use JsonSerializable;

final class SeoAiProviderUsageExtractor
{
    private const MAX_TOKEN_VALUE = 4_294_967_295;

    /**
     * Extract only provider-reported usage. Pricing is never inferred from
     * provider/model names because price tables change independently of code.
     */
    public function extract(mixed $rawResponse): ?SeoAiUsageData
    {
        $root = $this->arrayLike($rawResponse);

        if ($root === null) {
            return null;
        }

        $usage = $this->arrayLike($root['usage'] ?? null) ?? [];
        $usageMetadata = $this->arrayLike(
            $root['usageMetadata']
                ?? $root['usage_metadata']
                ?? null,
        ) ?? [];
        $normalized = $this->arrayLike($root['_mwt_usage'] ?? null) ?? [];

        $inputTokens = $this->firstToken([
            $normalized['input_tokens'] ?? null,
            $usage['input_tokens'] ?? null,
            $usage['prompt_tokens'] ?? null,
            $usageMetadata['promptTokenCount'] ?? null,
            $usageMetadata['prompt_token_count'] ?? null,
        ]);

        $outputTokens = $this->firstToken([
            $normalized['output_tokens'] ?? null,
            $usage['output_tokens'] ?? null,
            $usage['completion_tokens'] ?? null,
            $usageMetadata['candidatesTokenCount'] ?? null,
            $usageMetadata['candidates_token_count'] ?? null,
        ]);

        $totalTokens = $this->firstToken([
            $normalized['total_tokens'] ?? null,
            $usage['total_tokens'] ?? null,
            $usageMetadata['totalTokenCount'] ?? null,
            $usageMetadata['total_token_count'] ?? null,
        ]);

        if ($totalTokens === null && $inputTokens !== null && $outputTokens !== null) {
            $totalTokens = $this->safeTokenSum($inputTokens, $outputTokens);
        }

        [$costAmountMinor, $costCurrency] = $this->cost(
            normalized: $normalized,
            usage: $usage,
        );

        if (
            $inputTokens === null
            && $outputTokens === null
            && $totalTokens === null
            && $costAmountMinor === null
            && $costCurrency === null
        ) {
            return null;
        }

        return new SeoAiUsageData(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            totalTokens: $totalTokens,
            costAmountMinor: $costAmountMinor,
            costCurrency: $costCurrency,
        );
    }

    /** @return array<string, mixed>|null */
    private function arrayLike(mixed $value): ?array
    {
        if (is_array($value)) {
            /** @var array<string, mixed> $value */
            return $value;
        }

        if ($value instanceof JsonSerializable) {
            return $this->arrayLike($value->jsonSerialize());
        }

        if (is_object($value)) {
            /** @var array<string, mixed> $properties */
            $properties = get_object_vars($value);

            return $properties === [] ? null : $properties;
        }

        if (! is_string($value) || trim($value) === '' || strlen($value) > 2_097_152) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param  list<mixed>  $values
     */
    private function firstToken(array $values): ?int
    {
        foreach ($values as $value) {
            $parsed = $this->nonNegativeInteger($value, self::MAX_TOKEN_VALUE);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function safeTokenSum(int $left, int $right): ?int
    {
        if ($left > self::MAX_TOKEN_VALUE - $right) {
            return null;
        }

        return $left + $right;
    }

    /**
     * Cost is accepted only from an explicit normalized amount/currency pair.
     * No provider pricing table is embedded or inferred here.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $usage
     * @return array{0: ?int, 1: ?string}
     */
    private function cost(array $normalized, array $usage): array
    {
        $amount = $this->nonNegativeInteger(
            $normalized['cost_amount_minor']
                ?? $usage['cost_amount_minor']
                ?? null,
            PHP_INT_MAX,
        );

        $currency = $this->currency(
            $normalized['cost_currency']
                ?? $usage['cost_currency']
                ?? null,
        );

        if ($amount === null || $currency === null) {
            return [null, null];
        }

        return [$amount, $currency];
    }

    private function currency(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $currency = strtoupper(trim($value));

        return preg_match('/^[A-Z]{3}$/', $currency) === 1
            ? $currency
            : null;
    }

    private function nonNegativeInteger(mixed $value, int $max): ?int
    {
        if (is_int($value)) {
            return $value >= 0 && $value <= $max ? $value : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }

        if (strlen($value) > strlen((string) $max)) {
            return null;
        }

        $integer = (int) $value;

        return $integer >= 0 && $integer <= $max && (string) $integer === ltrim($value, '0')
            ? $integer
            : ($integer === 0 && preg_match('/^0+$/', $value) === 1 ? 0 : null);
    }
}
