<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Data;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisCategory;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisSeverity;

final readonly class AiSeoAnalysisFindingData
{
    public function __construct(
        public string $id,
        public AiSeoAnalysisCategory $category,
        public AiSeoAnalysisSeverity $severity,
        public string $title,
        public string $message,
        public string $recommendation,
    ) {}

    /**
     * @return array{
     *     id: string,
     *     category: string,
     *     severity: string,
     *     title: string,
     *     message: string,
     *     recommendation: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,

            'category' => $this->category->value,

            'severity' => $this->severity->value,

            'title' => $this->title,

            'message' => $this->message,

            'recommendation' => $this->recommendation,
        ];
    }
}
