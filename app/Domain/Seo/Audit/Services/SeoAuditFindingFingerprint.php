<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditFindingData;
use JsonException;

final class SeoAuditFindingFingerprint
{
    /**
     * @throws JsonException
     */
    public function make(SeoAuditFindingData $finding): string
    {
        $payload = [
            'rule' => $finding->rule->value,
            'subject_type' => $finding->subjectType->value,
            'subject_key' => $finding->subjectKey,
            'url' => $finding->url,
            'source_url' => $finding->sourceUrl,
            'evidence' => $this->normalize($finding->evidence),
        ];

        return hash(
            'sha256',
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }

        return $value;
    }
}
