<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use App\Domain\Seo\Ai\Data\AiSeoPromptContextData;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoRequest;
use JsonException;

final readonly class AiSeoDraftRequestService
{
    public function __construct(
        private AiSeoEntitySnapshotBuilder $snapshots,
        private AiSeoPromptContextFactory $promptContext,
        private AiSeoRequestLifecycleManager $lifecycle,
    ) {}

    /**
     * Build and persist the exact immutable context that a
     * provider will receive later in H.3.
     *
     * @throws JsonException
     */
    public function create(
        AiSeoEntityType $entityType,
        int $entityId,
        ?int $requestedByAdminId = null,
    ): AiSeoRequest {
        $snapshot =
            $this
                ->snapshots
                ->build(
                    $entityType,
                    $entityId,
                );

        $context =
            $this
                ->promptContext
                ->make(
                    $snapshot,
                );

        return $this
            ->lifecycle
            ->create(
                entityType: $entityType,

                entityId: $entityId,

                action: AiSeoAction::GenerateMetadataSuggestions,

                inputSnapshot: $context
                    ->toArray(),

                requestedByAdminId: $requestedByAdminId,
            );
    }

    public function promptVersion(): string
    {
        return AiSeoPromptContextData::PROMPT_VERSION;
    }
}
