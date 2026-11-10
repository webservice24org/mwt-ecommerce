<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;
use UnexpectedValueException;

final readonly class SubmitShipmentToProviderAction
{
    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_AUTOMATIC = 'automatic';

    public function __construct(
        private ShippingProviderRegistry $providers,
    ) {}

    /**
     * Submit one internal shipment to its configured external
     * shipping provider.
     *
     * A shipment owns exactly one provider submission row.
     * Every retry reuses the same idempotency key.
     *
     * @throws Throwable
     */
    public function execute(
        Shipment $shipment,
        string $submissionTrigger = self::TRIGGER_MANUAL,
    ): ShipmentProviderSubmission {
        $submissionTrigger =
            $this->normalizeSubmissionTrigger(
                $submissionTrigger,
            );

        $submission = $this->prepare(
            shipment: $shipment,

            submissionTrigger: $submissionTrigger,
        );

        if (
            $submission->status ===
            ShipmentProviderSubmissionStatus::Submitted
        ) {
            return $submission;
        }

        $provider = $this->providers->resolve(
            $submission->provider_code,
        );

        $providerShipment = Shipment::query()
            ->with([
                'order',
                'shippingMethod',
                'shippingProviderAccount',
                'items',
                'items.orderItem',
            ])
            ->findOrFail(
                $shipment->getKey(),
            );

        try {
            $result = $provider->createShipment(
                shipment: $providerShipment,

                idempotencyKey: $submission->idempotency_key,
            );

            $externalReference =
                $this->requiredExternalReference(
                    $result,
                );

            return $this->complete(
                submission: $submission,

                result: $result,

                externalReference: $externalReference,
            );
        } catch (
            ProviderSubmissionOutcomeUnknownException $exception
        ) {
            $this->markReviewRequired(
                $submission,
            );

            throw $exception;
        } catch (Throwable $exception) {
            $this->markFailed(
                $submission,
            );

            throw $exception;
        }
    }

    private function prepare(
        Shipment $shipment,
        string $submissionTrigger,
    ): ShipmentProviderSubmission {
        return DB::transaction(
            function () use (
                $shipment,
                $submissionTrigger,
            ): ShipmentProviderSubmission {
                /*
                 * Follow the fulfilment lock ordering:
                 *
                 * 1. Order
                 * 2. Shipment
                 * 3. Provider submission
                 */
                $lockedOrder = Order::query()
                    ->whereKey(
                        $shipment->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedShipment = Shipment::query()
                    ->whereKey(
                        $shipment->getKey(),
                    )
                    ->where(
                        'order_id',
                        $lockedOrder->id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing =
                    ShipmentProviderSubmission::query()
                        ->where(
                            'shipment_id',
                            $lockedShipment->id,
                        )
                        ->lockForUpdate()
                        ->first();

                /*
                 * Once submitted, the provider snapshot is
                 * authoritative. Repeated calls remain
                 * idempotent even if the shipment has since
                 * advanced to Shipped/Delivered.
                 */
                if (
                    $existing instanceof ShipmentProviderSubmission &&
                    $existing->status ===
                    ShipmentProviderSubmissionStatus::Submitted
                ) {
                    return $existing;
                }

                $this->validateShipmentState(
                    $lockedShipment,
                );

                /*
                 * The checkout delivery service must still exist,
                 * but it no longer determines the physical courier.
                 */
                $this->shippingMethod(
                    $lockedShipment,
                );

                $providerCode =
                    $lockedShipment->effectiveProviderCode();

                if ($providerCode === null) {
                    throw ValidationException::withMessages([
                        'provider' => 'This shipment does not have a courier provider.',
                    ]);
                }

                if (
                    ! $this->providers->has(
                        $providerCode,
                    )
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'The shipment courier provider is not registered.',
                    ]);
                }

                if (
                    $providerCode ===
                    ManualShippingProvider::CODE
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Manual shipments do not require external provider submission.',
                    ]);
                }

                if (
                    $existing instanceof ShipmentProviderSubmission
                ) {
                    if (
                        $existing->provider_code !==
                        $providerCode
                    ) {
                        throw ValidationException::withMessages([
                            'provider' => 'This shipment already belongs to a different provider submission.',
                        ]);
                    }

                    if (
                        $existing->status ===
                        ShipmentProviderSubmissionStatus::Submitting
                    ) {
                        throw ValidationException::withMessages([
                            'provider' => 'This shipment is already being submitted to the provider.',
                        ]);
                    }

                    if (
                        $existing->status ===
                        ShipmentProviderSubmissionStatus::ReviewRequired
                    ) {
                        throw ValidationException::withMessages([
                            'provider' => 'This provider submission requires manual review before any retry.',
                        ]);
                    }

                    $existing->status =
                        ShipmentProviderSubmissionStatus::Submitting;

                    $existing->attempt_count =
                        $existing->attempt_count + 1;

                    $existing->last_attempted_at =
                        now();

                    $existing->last_error =
                        null;

                    $existingMetadata =
                        is_array(
                            $existing->metadata,
                        )
                        ? $existing->metadata
                        : [];

                    if (
                        ! isset(
                            $existingMetadata[
                                'submission_trigger'
                            ],
                        )
                    ) {
                        $existingMetadata[
                            'submission_trigger'
                        ] = $submissionTrigger;
                    }

                    $existingMetadata[
                        'last_submission_trigger'
                    ] = $submissionTrigger;

                    $existing->metadata =
                        $existingMetadata;

                    $existing->save();

                    return $existing->refresh();
                }

                return ShipmentProviderSubmission::query()
                    ->create([
                        'shipment_id' => $lockedShipment->id,

                        'provider_code' => $providerCode,

                        'idempotency_key' => (string) Str::uuid(),

                        'status' => ShipmentProviderSubmissionStatus::Submitting,

                        'external_reference' => null,

                        'attempt_count' => 1,

                        'last_attempted_at' => now(),

                        'submitted_at' => null,

                        'last_error' => null,

                        'metadata' => [
                            'submission_trigger' => $submissionTrigger,

                            'last_submission_trigger' => $submissionTrigger,
                        ],
                    ]);
            },
        );
    }

    private function complete(
        ShipmentProviderSubmission $submission,
        ProviderShipmentSubmissionResult $result,
        string $externalReference,
    ): ShipmentProviderSubmission {
        $shipmentSnapshot = Shipment::query()
            ->select([
                'id',
                'order_id',
            ])
            ->findOrFail(
                $submission->shipment_id,
            );

        return DB::transaction(
            function () use ($submission, $result, $externalReference, $shipmentSnapshot): ShipmentProviderSubmission {
                /*
                 * Preserve the same lock ordering used by the
                 * fulfilment actions:
                 *
                 * 1. Order
                 * 2. Shipment
                 * 3. Provider submission
                 */
                Order::query()
                    ->whereKey(
                        $shipmentSnapshot->order_id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedShipment = Shipment::query()
                    ->whereKey(
                        $shipmentSnapshot->id,
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->where(
                            'shipment_id',
                            $lockedShipment->id,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedSubmission->status ===
                    ShipmentProviderSubmissionStatus::Submitted
                ) {
                    return $lockedSubmission;
                }

                if (
                    $lockedSubmission->status !==
                    ShipmentProviderSubmissionStatus::Submitting
                ) {
                    throw ValidationException::withMessages([
                        'provider' => 'Provider submission is no longer in a submittable state.',
                    ]);
                }

                /*
                 * Validate provider-controlled values before
                 * any database mutation is committed.
                 */
                $tracking =
                    $this->validatedProviderTracking(
                        $result,
                    );

                $lockedSubmission->status =
                    ShipmentProviderSubmissionStatus::Submitted;

                $lockedSubmission->external_reference =
                    $externalReference;

                $lockedSubmission->submitted_at =
                    now();

                $lockedSubmission->last_error =
                    null;

                $providerMetadata =
                    is_array(
                        $result->metadata,
                    )
                    ? $result->metadata
                    : [];

                $submissionMetadata =
                    is_array(
                        $lockedSubmission->metadata,
                    )
                    ? $lockedSubmission->metadata
                    : [];

                /*
                 * Provider metadata is useful, but submission
                 * audit keys belong to our application and must
                 * survive successful provider completion.
                 */
                $lockedSubmission->metadata =
                    array_merge(
                        $providerMetadata,
                        $submissionMetadata,
                    );

                $lockedSubmission->save();

                $this->applyProviderTracking(
                    shipment: $lockedShipment,

                    carrier: $tracking['carrier'],

                    service: $tracking['service'],

                    trackingNumber: $tracking['tracking_number'],

                    trackingUrl: $tracking['tracking_url'],
                );

                return $lockedSubmission
                    ->refresh()
                    ->load(
                        'shipment',
                    );
            },
        );
    }

    private function normalizeSubmissionTrigger(
        string $submissionTrigger,
    ): string {
        $submissionTrigger =
            strtolower(
                trim(
                    $submissionTrigger,
                ),
            );

        if (
            ! in_array(
                $submissionTrigger,
                [
                    self::TRIGGER_MANUAL,
                    self::TRIGGER_AUTOMATIC,
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'Unsupported shipment provider submission trigger.',
            );
        }

        return $submissionTrigger;
    }

    private function markReviewRequired(
        ShipmentProviderSubmission $submission,
    ): void {
        DB::transaction(
            function () use ($submission): void {
                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $lockedSubmission instanceof ShipmentProviderSubmission ||
                    $lockedSubmission->status ===
                    ShipmentProviderSubmissionStatus::Submitted
                ) {
                    return;
                }

                $lockedSubmission->status =
                    ShipmentProviderSubmissionStatus::ReviewRequired;

                $lockedSubmission->last_error =
                    'Provider submission outcome is uncertain. Manual provider review is required before retry.';

                $lockedSubmission->save();
            },
        );
    }

    private function markFailed(
        ShipmentProviderSubmission $submission,
    ): void {
        DB::transaction(
            function () use ($submission): void {
                $lockedSubmission =
                    ShipmentProviderSubmission::query()
                        ->whereKey(
                            $submission->getKey(),
                        )
                        ->lockForUpdate()
                        ->first();

                if (
                    ! $lockedSubmission instanceof ShipmentProviderSubmission ||
                    $lockedSubmission->status ===
                    ShipmentProviderSubmissionStatus::Submitted
                ) {
                    return;
                }

                $lockedSubmission->status =
                    ShipmentProviderSubmissionStatus::Failed;

                /*
                 * Do not persist raw provider exception text.
                 * Adapters may contain tokens, endpoints or other
                 * sensitive diagnostics in exception messages.
                 */
                $lockedSubmission->last_error =
                    'Provider shipment submission failed.';

                $lockedSubmission->save();
            },
        );
    }

    private function validateShipmentState(
        Shipment $shipment,
    ): void {
        if (
            ! in_array(
                $shipment->status,
                [
                    ShipmentStatus::Pending,
                    ShipmentStatus::Processing,
                ],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'shipment_status' => 'Only pending or processing shipments can be submitted to a shipping provider.',
            ]);
        }
    }

    private function shippingMethod(
        Shipment $shipment,
    ): ShippingMethod {
        if (
            $shipment->shipping_method_id ===
            null
        ) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'The shipment has no shipping method.',
            ]);
        }

        return ShippingMethod::query()
            ->findOrFail(
                $shipment->shipping_method_id,
            );
    }

    private function requiredExternalReference(
        ProviderShipmentSubmissionResult $result,
    ): string {
        $reference = trim(
            (string) $result->externalReference,
        );

        if ($reference === '') {
            throw new UnexpectedValueException(
                'The shipping provider did not return an external shipment reference.',
            );
        }

        if (mb_strlen($reference) > 190) {
            throw new UnexpectedValueException(
                'The shipping provider returned an external shipment reference that is too long.',
            );
        }

        return $reference;
    }

    /**
     * @return array{
     *     carrier: string|null,
     *     service: string|null,
     *     tracking_number: string|null,
     *     tracking_url: string|null
     * }
     */
    private function validatedProviderTracking(
        ProviderShipmentSubmissionResult $result,
    ): array {
        $trackingUrl =
            $this->providerString(
                value: $result->trackingUrl,

                maximumLength: 2048,

                field: 'tracking URL',
            );

        if (
            $trackingUrl !== null &&
            ! $this->isHttpUrl(
                $trackingUrl,
            )
        ) {
            throw new UnexpectedValueException(
                'The shipping provider returned an invalid tracking URL.',
            );
        }

        return [
            'carrier' => $this->providerString(
                value: $result->carrier,

                maximumLength: 120,

                field: 'carrier',
            ),

            'service' => $this->providerString(
                value: $result->service,

                maximumLength: 120,

                field: 'service',
            ),

            'tracking_number' => $this->providerString(
                value: $result->trackingNumber,

                maximumLength: 190,

                field: 'tracking number',
            ),

            'tracking_url' => $trackingUrl,
        ];
    }

    private function applyProviderTracking(
        Shipment $shipment,
        ?string $carrier,
        ?string $service,
        ?string $trackingNumber,
        ?string $trackingUrl,
    ): void {
        if ($carrier !== null) {
            $shipment->carrier =
                $carrier;
        }

        if ($service !== null) {
            $shipment->service =
                $service;
        }

        if ($trackingNumber !== null) {
            $shipment->tracking_number =
                $trackingNumber;
        }

        if ($trackingUrl !== null) {
            $shipment->tracking_url =
                $trackingUrl;
        }

        $shipment->save();
    }

    private function providerString(
        ?string $value,
        int $maximumLength,
        string $field,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            $value,
        );

        if ($value === '') {
            return null;
        }

        if (
            mb_strlen(
                $value,
            ) >
            $maximumLength
        ) {
            throw new UnexpectedValueException(
                sprintf(
                    'The shipping provider returned a %s value that is too long.',
                    $field,
                ),
            );
        }

        return $value;
    }

    private function isHttpUrl(
        string $value,
    ): bool {
        if (
            filter_var(
                $value,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            return false;
        }

        $scheme = strtolower(
            (string) parse_url(
                $value,
                PHP_URL_SCHEME,
            ),
        );

        return in_array(
            $scheme,
            [
                'http',
                'https',
            ],
            true,
        );
    }
}
