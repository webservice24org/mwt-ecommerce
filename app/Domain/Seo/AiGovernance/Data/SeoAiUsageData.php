<?php

declare(strict_types=1);

namespace App\Domain\Seo\AiGovernance\Data;

use InvalidArgumentException;

final readonly class SeoAiUsageData
{
    public function __construct(
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $totalTokens = null,
        public ?int $costAmountMinor = null,
        public ?string $costCurrency = null,
    ) {
        foreach ([$this->inputTokens, $this->outputTokens, $this->totalTokens, $this->costAmountMinor] as $value) {
            if ($value !== null && $value < 0) {
                throw new InvalidArgumentException('AI usage values cannot be negative.');
            }
        }

        if ($this->costCurrency !== null && preg_match('/^[A-Z]{3}$/', $this->costCurrency) !== 1) {
            throw new InvalidArgumentException('AI cost currency must be a three-letter uppercase code.');
        }
    }
}
