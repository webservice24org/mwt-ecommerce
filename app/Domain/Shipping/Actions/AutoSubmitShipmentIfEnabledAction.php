<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Actions;

use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingProviderAccount;
use Illuminate\Validation\ValidationException;

final readonly class AutoSubmitShipmentIfEnabledAction
{
    public function __construct(
        private SubmitShipmentToProviderAction $submitShipment,
    ) {}

    /**
     * Auto-submit a newly-created shipment only when its
     * explicitly linked external courier account has
     * auto_submit enabled.
     *
     * IMPORTANT:
     * This action must run only after CreateShipmentAction
     * returns, so the external courier API request never runs
     * inside the shipment creation transaction.
     */
    public function execute(
        Shipment $shipment,
    ): ?ShipmentProviderSubmission {
        $shipment =
            Shipment::query()
                ->with([
                    'shippingProviderAccount',
                ])
                ->findOrFail(
                    $shipment->getKey(),
                );

        $providerCode =
            $shipment
                ->effectiveProviderCode();

        /*
         * Legacy or incomplete shipments do not have enough
         * information for automatic provider submission.
         */
        if (
            $providerCode === null
            ||
            trim(
                $providerCode,
            ) === ''
        ) {
            return null;
        }

        /*
         * Manual fulfilment has no external API side effect.
         */
        if (
            $providerCode ===
            ManualShippingProvider::CODE
        ) {
            return null;
        }

        $account =
            $shipment
                ->shippingProviderAccount;

        if (
            ! $account instanceof ShippingProviderAccount
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Automatic provider submission requires the shipment to be linked to a courier account.',
            ]);
        }

        /*
         * The linked account must belong to the same physical
         * courier selected on the shipment.
         */
        if (
            $account
                ->provider_code
                ->value !==
            $providerCode
        ) {
            throw ValidationException::withMessages([
                'provider' => 'The shipment courier account does not match its selected provider.',
            ]);
        }

        /*
         * is_default controls initial UI selection only.
         *
         * auto_submit is the only switch that enables the
         * automatic external API side effect.
         */
        if (
            ! $account
                ->auto_submit
        ) {
            return null;
        }

        /*
         * Re-check the account immediately before the API call.
         * The provider may have been disabled or disconnected
         * after the shipment form was loaded.
         */
        if (
            ! $account
                ->isOperational()
        ) {
            throw ValidationException::withMessages([
                'provider' => 'Automatic provider submission is enabled, but the courier account is no longer connected and active.',
            ]);
        }

        /*
         * SubmitShipmentToProviderAction remains responsible for:
         *
         * - provider idempotency
         * - retries
         * - Failed state
         * - Review Required state
         * - external reference persistence
         * - tracking persistence
         *
         * 8.8F.3 additionally records that this provider
         * submission was triggered automatically.
         */
        return $this
            ->submitShipment
            ->execute(
                shipment: $shipment,

                submissionTrigger: SubmitShipmentToProviderAction::TRIGGER_AUTOMATIC,
            );
    }
}
