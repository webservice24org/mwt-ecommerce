<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Services;

use App\Domain\Seo\StructuredData\Data\StructuredDataDocument;
use JsonException;

final class StructuredDataSerializer
{
    /**
     * @throws JsonException
     */
    public function serialize(
        StructuredDataDocument $document,
    ): string {
        return json_encode(
            $document->toArray(),
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE,
        );
    }
}
