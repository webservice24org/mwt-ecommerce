<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use JsonException;

final class AiSeoSnapshotHasher
{
    /**
     * @param  array<string, mixed>  $snapshot
     *
     * @throws JsonException
     */
    public function hash(
        array $snapshot,
    ): string {
        $normalized =
            $this->normalize(
                $snapshot,
            );

        return hash(
            'sha256',
            json_encode(
                $normalized,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE,
            ),
        );
    }

    private function normalize(
        mixed $value,
    ): mixed {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->normalize(
                    $item,
                ),
                $value,
            );
        }

        ksort(
            $value,
            SORT_STRING,
        );

        foreach (
            $value as $key => $item
        ) {
            $value[$key] =
                $this->normalize(
                    $item,
                );
        }

        return $value;
    }
}
