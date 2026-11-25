<?php

declare(strict_types=1);

namespace App\Domain\Seo\GenerativeSearch\Data;

final readonly class AiCrawlerPolicyData
{
    public function __construct(
        public bool $allowOaiSearchBot,
        public bool $allowGptBot,
        public bool $allowGoogleExtended,
        public int $version,
    ) {}

    /**
     * @return array{
     *     allow_oai_search_bot: bool,
     *     allow_gpt_bot: bool,
     *     allow_google_extended: bool,
     *     version: int
     * }
     */
    public function toArray(): array
    {
        return [
            'allow_oai_search_bot' => $this->allowOaiSearchBot,

            'allow_gpt_bot' => $this->allowGptBot,

            'allow_google_extended' => $this->allowGoogleExtended,

            'version' => $this->version,
        ];
    }
}
