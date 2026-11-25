<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Services;

use App\Domain\Seo\Ai\Data\AiSeoSuggestionData;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Throwable;

final readonly class AiSeoRequestLifecycleManager
{
    public function __construct(
        private AiSeoSnapshotHasher $snapshotHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $inputSnapshot
     *
     * @throws JsonException
     */
    public function create(
        AiSeoEntityType $entityType,
        int $entityId,
        AiSeoAction $action,
        array $inputSnapshot,
        ?int $requestedByAdminId = null,
    ): AiSeoRequest {
        if ($entityId <= 0) {
            throw new InvalidArgumentException(
                'AI SEO entity ID must be positive.',
            );
        }

        if ($inputSnapshot === []) {
            throw new InvalidArgumentException(
                'AI SEO input snapshot cannot be empty.',
            );
        }

        return AiSeoRequest::query()
            ->create([
                'entity_type' => $entityType,

                'entity_id' => $entityId,

                'action' => $action,

                'status' => AiSeoRequestStatus::Pending,

                'requested_by_admin_id' => $requestedByAdminId,

                'input_snapshot' => $inputSnapshot,

                'input_hash' => $this
                    ->snapshotHasher
                    ->hash(
                        $inputSnapshot,
                    ),
            ]);
    }

    public function start(
        AiSeoRequest $request,
        string $provider,
        string $modelName,
        string $promptVersion,
    ): AiSeoRequest {
        $provider =
            $this->requiredString(
                $provider,
                'AI SEO provider is required.',
            );

        $modelName =
            $this->requiredString(
                $modelName,
                'AI SEO model name is required.',
            );

        $promptVersion =
            $this->requiredString(
                $promptVersion,
                'AI SEO prompt version is required.',
            );

        return DB::transaction(
            function () use (
                $request,
                $provider,
                $modelName,
                $promptVersion,
            ): AiSeoRequest {
                $locked =
                    $this->lock(
                        $request,
                    );

                $this->requireStatus(
                    $locked,
                    AiSeoRequestStatus::Pending,
                );

                $locked->update([
                    'status' => AiSeoRequestStatus::Processing,

                    'provider' => $provider,

                    'model_name' => $modelName,

                    'prompt_version' => $promptVersion,

                    'started_at' => now(),

                    'error_code' => null,

                    'error_message' => null,
                ]);

                return $locked->refresh();
            },
        );
    }

    /**
     * @param  list<AiSeoSuggestionData>  $suggestions
     * @param  array<string, mixed>|null  $rawResponse
     *
     * @throws Throwable
     */
    public function complete(
        AiSeoRequest $request,
        array $suggestions,
        ?array $rawResponse = null,
    ): AiSeoRequest {
        $this->validateSuggestions(
            $suggestions,
        );

        return DB::transaction(
            function () use (
                $request,
                $suggestions,
                $rawResponse,
            ): AiSeoRequest {
                $locked =
                    $this->lock(
                        $request,
                    );

                $this->requireStatus(
                    $locked,
                    AiSeoRequestStatus::Processing,
                );

                foreach (
                    $suggestions as $position => $suggestion
                ) {
                    AiSeoSuggestion::query()
                        ->create([
                            'ai_seo_request_id' => $locked->id,

                            'field' => $suggestion->field,

                            'value' => trim(
                                $suggestion->value,
                            ),

                            'rationale' => $this->nullableString(
                                $suggestion->rationale,
                            ),

                            'position' => $position,
                        ]);
                }

                $locked->update([
                    'status' => AiSeoRequestStatus::Completed,

                    'raw_response' => $rawResponse,

                    'completed_at' => now(),
                ]);

                return $locked
                    ->refresh()
                    ->load(
                        'suggestions',
                    );
            },
        );
    }

    public function fail(
        AiSeoRequest $request,
        string $errorCode,
        string $errorMessage,
    ): AiSeoRequest {
        $errorCode =
            $this->requiredString(
                $errorCode,
                'AI SEO error code is required.',
            );

        $errorMessage =
            $this->requiredString(
                $errorMessage,
                'AI SEO error message is required.',
            );

        return DB::transaction(
            function () use (
                $request,
                $errorCode,
                $errorMessage,
            ): AiSeoRequest {
                $locked =
                    $this->lock(
                        $request,
                    );

                if (
                    ! in_array(
                        $locked->status,
                        [
                            AiSeoRequestStatus::Pending,
                            AiSeoRequestStatus::Processing,
                        ],
                        true,
                    )
                ) {
                    throw $this->transitionException(
                        $locked,
                    );
                }

                $locked->update([
                    'status' => AiSeoRequestStatus::Failed,

                    'error_code' => $errorCode,

                    'error_message' => $errorMessage,

                    'completed_at' => now(),
                ]);

                return $locked->refresh();
            },
        );
    }

    public function cancel(
        AiSeoRequest $request,
    ): AiSeoRequest {
        return DB::transaction(
            function () use (
                $request,
            ): AiSeoRequest {
                $locked =
                    $this->lock(
                        $request,
                    );

                if (
                    ! in_array(
                        $locked->status,
                        [
                            AiSeoRequestStatus::Pending,
                            AiSeoRequestStatus::Processing,
                        ],
                        true,
                    )
                ) {
                    throw $this->transitionException(
                        $locked,
                    );
                }

                $locked->update([
                    'status' => AiSeoRequestStatus::Cancelled,

                    'cancelled_at' => now(),
                ]);

                return $locked->refresh();
            },
        );
    }

    private function lock(
        AiSeoRequest $request,
    ): AiSeoRequest {
        return AiSeoRequest::query()
            ->lockForUpdate()
            ->findOrFail(
                $request->id,
            );
    }

    private function requireStatus(
        AiSeoRequest $request,
        AiSeoRequestStatus $status,
    ): void {
        if (
            $request->status !== $status
        ) {
            throw $this->transitionException(
                $request,
            );
        }
    }

    private function transitionException(
        AiSeoRequest $request,
    ): DomainException {
        return new DomainException(
            sprintf(
                'AI SEO request %d cannot transition from status %s.',
                $request->id,
                $request->status->value,
            ),
        );
    }

    /**
     * @param  list<AiSeoSuggestionData>  $suggestions
     */
    private function validateSuggestions(
        array $suggestions,
    ): void {
        if ($suggestions === []) {
            throw new InvalidArgumentException(
                'AI SEO completion requires at least one suggestion.',
            );
        }

        $fields = [];

        foreach (
            $suggestions as $suggestion
        ) {
            if (
                trim(
                    $suggestion->value,
                ) === ''
            ) {
                throw new InvalidArgumentException(
                    'AI SEO suggestion value cannot be blank.',
                );
            }

            $field =
                $suggestion
                    ->field
                    ->value;

            if (
                isset(
                    $fields[$field],
                )
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Duplicate AI SEO suggestion field: %s.',
                        $field,
                    ),
                );
            }

            $fields[$field] =
                true;
        }
    }

    private function requiredString(
        string $value,
        string $message,
    ): string {
        $value =
            trim(
                $value,
            );

        if ($value === '') {
            throw new InvalidArgumentException(
                $message,
            );
        }

        return $value;
    }

    private function nullableString(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                $value,
            );

        return $value === ''
            ? null
            : $value;
    }
}
