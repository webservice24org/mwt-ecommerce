<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Data;

use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;

final readonly class AiSeoSuggestionData
{
    public function __construct(
        public AiSeoSuggestionField $field,
        public string $value,
        public ?string $rationale = null,
    ) {}
}
