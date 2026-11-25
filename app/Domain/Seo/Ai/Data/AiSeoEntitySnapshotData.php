<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Data;

use App\Domain\Seo\Ai\Enums\AiSeoEntityType;

final readonly class AiSeoEntitySnapshotData
{
    public const SCHEMA_VERSION =
        'ai-seo-entity-snapshot-v1';

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $context
     * @param  array<string, string|null>  $currentSeo
     */
    public function __construct(
        public AiSeoEntityType $entityType,
        public int $entityId,
        public string $name,
        public string $slug,
        public array $content,
        public array $context,
        public array $currentSeo,
    ) {}

    /**
     * @return array{
     *     schema_version: string,
     *     entity: array{
     *         type: string,
     *         id: int,
     *         name: string,
     *         slug: string
     *     },
     *     content: array<string, mixed>,
     *     context: array<string, mixed>,
     *     current_seo: array<string, string|null>
     * }
     */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,

            'entity' => [
                'type' => $this->entityType->value,

                'id' => $this->entityId,

                'name' => $this->name,

                'slug' => $this->slug,
            ],

            'content' => $this->content,

            'context' => $this->context,

            'current_seo' => $this->currentSeo,
        ];
    }
}
