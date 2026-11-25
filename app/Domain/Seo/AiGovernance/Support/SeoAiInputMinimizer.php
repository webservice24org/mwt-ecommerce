<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Support;

use App\Domain\Seo\AiGovernance\Data\SeoAiSafeInputData;
use InvalidArgumentException;
use JsonException;

final class SeoAiInputMinimizer
{
    private const MAX_DEPTH = 8;

    private const MAX_JSON_BYTES = 65_536;

    /** @var list<string> */
    private const ALLOWED_ROOT_KEYS = [
        'entity_type',
        'entity_key',
        'title',
        'description',
        'slug',
        'brand',
        'categories',
        'attributes',
        'images',
        'current_seo',
        'public_url',
        'locale',
    ];

    /** @var list<string> */
    private const FORBIDDEN_KEY_FRAGMENTS = [
        'customer',
        'order',
        'billing',
        'shipping_address',
        'address_line',
        'payment',
        'card',
        'bank',
        'password',
        'secret',
        'access_token',
        'refresh_token',
        'private_key',
        'admin_private',
        'private_admin',
    ];

    /**
     * @param  array<string, mixed>  $input
     */
    public function minimize(array $input): SeoAiSafeInputData
    {
        foreach (array_keys($input) as $key) {
            if (! in_array($key, self::ALLOWED_ROOT_KEYS, true)) {
                throw new InvalidArgumentException("AI input field [{$key}] is not allowed by the SEO data-minimization policy.");
            }
        }

        $normalized = $this->normalize($input, 0);

        if (! is_array($normalized)) {
            throw new InvalidArgumentException('AI input must normalize to an object.');
        }

        /** @var array<string, mixed> $normalized */
        $json = $this->encode($normalized);

        if (strlen($json) > self::MAX_JSON_BYTES) {
            throw new InvalidArgumentException('AI input exceeds the bounded SEO governance payload size.');
        }

        $fieldNames = array_keys($normalized);
        sort($fieldNames, SORT_STRING);

        return new SeoAiSafeInputData(
            payload: $normalized,
            snapshotHash: hash('sha256', $json),
            fieldNames: $fieldNames,
        );
    }

    private function assertSafeKey(string $key): void
    {
        $normalized = strtolower($key);

        foreach (self::FORBIDDEN_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                throw new InvalidArgumentException("AI input field [{$key}] is forbidden by the SEO data-minimization policy.");
            }
        }
    }

    private function normalize(mixed $value, int $depth): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            throw new InvalidArgumentException('AI input exceeds the maximum nesting depth.');
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map(
                    fn (mixed $item): mixed => $this->normalize($item, $depth + 1),
                    $value,
                );
            }

            $normalized = [];
            $keys = array_keys($value);
            sort($keys, SORT_STRING);

            foreach ($keys as $key) {
                if (! is_string($key)) {
                    throw new InvalidArgumentException('AI input object keys must be strings.');
                }

                $this->assertSafeKey($key);
                $normalized[$key] = $this->normalize($value[$key], $depth + 1);
            }

            return $normalized;
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        throw new InvalidArgumentException('AI input may contain only scalar values, nulls, lists, and objects.');
    }

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        try {
            return json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('AI input cannot be encoded safely.', previous: $exception);
        }
    }
}
