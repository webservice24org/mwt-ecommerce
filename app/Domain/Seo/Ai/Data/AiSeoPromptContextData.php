<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Data;

final readonly class AiSeoPromptContextData
{
    public const PROMPT_VERSION =
        'ai-seo-metadata-v1';

    /**
     * @param  list<string>  $rules
     * @param  array<string, array{
     *     max_length: int,
     *     purpose: string
     * }>  $targetFields
     * @param  array<string, mixed>  $entitySnapshot
     */
    public function __construct(
        public string $siteName,
        public array $rules,
        public array $targetFields,
        public array $entitySnapshot,
    ) {}

    /**
     * @return array{
     *     prompt_version: string,
     *     task: string,
     *     site: array{name: string},
     *     rules: list<string>,
     *     target_fields: array<string, array{
     *         max_length: int,
     *         purpose: string
     *     }>,
     *     entity_snapshot: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'prompt_version' => self::PROMPT_VERSION,

            'task' => 'generate_metadata_suggestions',

            'site' => [
                'name' => $this->siteName,
            ],

            'rules' => $this->rules,

            'target_fields' => $this->targetFields,

            'entity_snapshot' => $this->entitySnapshot,
        ];
    }
}
