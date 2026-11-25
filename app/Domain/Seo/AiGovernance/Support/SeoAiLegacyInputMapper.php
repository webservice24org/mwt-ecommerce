<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Support;

final class SeoAiLegacyInputMapper
{
    /**
     * Map the existing AI SEO prompt snapshot into the P.1 governance
     * allowlist. Prompt/provider metadata is intentionally not copied into
     * the governed provider payload because it is stored separately on the
     * governance run.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function map(array $snapshot): array
    {
        $mapped = [];

        $this->putString(
            $mapped,
            'entity_type',
            $this->first($snapshot, [
                ['entity_type'],
                ['entity', 'type'],
                ['subject', 'type'],
            ]),
        );

        $this->putString(
            $mapped,
            'entity_key',
            $this->first($snapshot, [
                ['entity_key'],
                ['entity', 'key'],
                ['entity', 'id'],
                ['entity', 'slug'],
                ['subject', 'key'],
            ]),
        );

        $this->putString(
            $mapped,
            'title',
            $this->first($snapshot, [
                ['title'],
                ['entity', 'title'],
                ['entity', 'name'],
            ]),
        );

        $this->putString(
            $mapped,
            'description',
            $this->first($snapshot, [
                ['description'],
                ['entity', 'description'],
                ['entity', 'short_description'],
            ]),
        );

        $this->putString(
            $mapped,
            'slug',
            $this->first($snapshot, [
                ['slug'],
                ['entity', 'slug'],
            ]),
        );

        $this->putValue(
            $mapped,
            'brand',
            $this->first($snapshot, [
                ['brand'],
                ['entity', 'brand'],
            ]),
        );

        $this->putValue(
            $mapped,
            'categories',
            $this->first($snapshot, [
                ['categories'],
                ['entity', 'categories'],
            ]),
        );

        $this->putValue(
            $mapped,
            'attributes',
            $this->first($snapshot, [
                ['attributes'],
                ['entity', 'attributes'],
            ]),
        );

        $this->putValue(
            $mapped,
            'images',
            $this->first($snapshot, [
                ['images'],
                ['entity', 'images'],
            ]),
        );

        $this->putValue(
            $mapped,
            'current_seo',
            $this->first($snapshot, [
                ['current_seo'],
                ['seo'],
                ['entity', 'current_seo'],
                ['entity', 'seo'],
            ]),
        );

        $this->putString(
            $mapped,
            'public_url',
            $this->first($snapshot, [
                ['public_url'],
                ['entity', 'public_url'],
                ['entity', 'url'],
            ]),
        );

        $this->putString(
            $mapped,
            'locale',
            $this->first($snapshot, [
                ['locale'],
                ['entity', 'locale'],
            ]),
        );

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function putString(
        array &$payload,
        string $key,
        mixed $value,
    ): void {
        if (! is_string($value) && ! is_int($value)) {
            return;
        }

        $normalized = trim((string) $value);

        if ($normalized !== '') {
            $payload[$key] = $normalized;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function putValue(
        array &$payload,
        string $key,
        mixed $value,
    ): void {
        if ($value === null || $value === '' || $value === []) {
            return;
        }

        if (
            is_string($value)
            || is_int($value)
            || is_float($value)
            || is_bool($value)
            || is_array($value)
        ) {
            $payload[$key] = $value;
        }
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<list<string>>  $paths
     */
    private function first(array $source, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = $this->at($source, $path);

            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $path
     */
    private function at(array $source, array $path): mixed
    {
        $value = $source;

        foreach ($path as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
