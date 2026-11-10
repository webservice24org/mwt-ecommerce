<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\AutoSubmitShipmentIfEnabledAction;
use App\Domain\Shipping\Actions\SubmitShipmentToProviderAction;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Exceptions\ProviderSubmissionOutcomeUnknownException;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Services\ShippingProviderAccountManager;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\ShippingProviderAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AutoSubmitFailureSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_definitive_auto_submit_failure_keeps_shipment_and_marks_submission_failed(): void
    {
        $account =
            $this->providerAccount();

        $provider =
            new FakeRejectedAutoSubmitProvider;

        $this->registerProvider(
            $provider,
        );

        $shipment =
            $this->shipment(
                $account,
            );

        try {
            app(
                AutoSubmitShipmentIfEnabledAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'A definitive provider rejection should fail the automatic submission.',
            );
        } catch (ValidationException) {
            //
        }

        /*
         * Shipment creation has already committed and must never
         * be rolled back/deleted because the external courier
         * rejected the provider submission.
         */
        $this->assertDatabaseHas(
            'shipments',
            [
                'id' => $shipment->id,

                'order_id' => $shipment->order_id,

                'provider_code' => ShippingProviderCode::Pathao
                    ->value,
            ],
        );

        $this->assertSame(
            ShipmentStatus::Pending,
            $shipment
                ->refresh()
                ->status,
        );

        $submission =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Failed,
            $submission->status,
        );

        $this->assertSame(
            1,
            $submission->attempt_count,
        );

        $this->assertSame(
            'Provider shipment submission failed.',
            $submission->last_error,
        );

        $metadata =
            $submission->metadata;

        $this->assertIsArray(
            $metadata,
        );

        $this->assertSame(
            SubmitShipmentToProviderAction::TRIGGER_AUTOMATIC,
            $metadata[
                'submission_trigger'
            ] ?? null,
        );

        $this->assertSame(
            SubmitShipmentToProviderAction::TRIGGER_AUTOMATIC,
            $metadata[
                'last_submission_trigger'
            ] ?? null,
        );

        $this->assertSame(
            1,
            $provider->createCount,
        );
    }

    public function test_ambiguous_auto_submit_keeps_shipment_marks_review_required_and_blocks_blind_retry(): void
    {
        $account =
            $this->providerAccount();

        $provider =
            new FakeAmbiguousAutoSubmitProvider;

        $this->registerProvider(
            $provider,
        );

        $shipment =
            $this->shipment(
                $account,
            );

        try {
            app(
                AutoSubmitShipmentIfEnabledAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'An ambiguous provider outcome should require manual review.',
            );
        } catch (
            ProviderSubmissionOutcomeUnknownException
        ) {
            //
        }

        $this->assertDatabaseHas(
            'shipments',
            [
                'id' => $shipment->id,

                'order_id' => $shipment->order_id,
            ],
        );

        $submission =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::ReviewRequired,
            $submission->status,
        );

        $this->assertSame(
            1,
            $submission->attempt_count,
        );

        $this->assertSame(
            'Provider submission outcome is uncertain. Manual provider review is required before retry.',
            $submission->last_error,
        );

        $metadata =
            $submission->metadata;

        $this->assertIsArray(
            $metadata,
        );

        $this->assertSame(
            SubmitShipmentToProviderAction::TRIGGER_AUTOMATIC,
            $metadata[
                'submission_trigger'
            ] ?? null,
        );

        /*
         * A second automatic execution must be blocked before
         * another external request can be sent.
         */
        try {
            app(
                AutoSubmitShipmentIfEnabledAction::class,
            )->execute(
                $shipment->refresh(),
            );

            $this->fail(
                'Review-required provider submissions must not retry automatically.',
            );
        } catch (ValidationException) {
            //
        }

        /*
         * Manual Submit-to-provider must also remain blocked
         * until ResolveShipmentProviderReviewAction explicitly
         * resolves the uncertain provider outcome.
         */
        try {
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment->refresh(),
            );

            $this->fail(
                'Review-required provider submissions must not allow blind manual retry.',
            );
        } catch (ValidationException) {
            //
        }

        $submission->refresh();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::ReviewRequired,
            $submission->status,
        );

        $this->assertSame(
            1,
            $submission->attempt_count,
        );

        /*
         * Most importantly, neither blocked retry reached the
         * provider again.
         */
        $this->assertSame(
            1,
            $provider->createCount,
        );
    }

    public function test_manual_retry_after_definitive_auto_failure_preserves_original_trigger_and_records_manual_retry(): void
    {
        $account =
            $this->providerAccount();

        $provider =
            new FakeRecoveringAutoSubmitProvider;

        $this->registerProvider(
            $provider,
        );

        $shipment =
            $this->shipment(
                $account,
            );

        /*
         * First request is an automatic definitive failure.
         */
        try {
            app(
                AutoSubmitShipmentIfEnabledAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'The first provider attempt should fail.',
            );
        } catch (ValidationException) {
            //
        }

        $failed =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $originalKey =
            $failed->idempotency_key;

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Failed,
            $failed->status,
        );

        /*
         * A definitive Failed submission is retryable. This
         * simulates the admin clicking "Retry submission", whose
         * default trigger is manual.
         */
        $submitted =
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment->refresh(),
            );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitted,
            $submitted->status,
        );

        $this->assertSame(
            2,
            $submitted->attempt_count,
        );

        /*
         * Provider idempotency must survive the retry.
         */
        $this->assertSame(
            $originalKey,
            $submitted->idempotency_key,
        );

        $this->assertSame(
            'RECOVERED-REFERENCE-123',
            $submitted->external_reference,
        );

        $metadata =
            $submitted->metadata;

        $this->assertIsArray(
            $metadata,
        );

        /*
         * Historical origin remains Automatic...
         */
        $this->assertSame(
            SubmitShipmentToProviderAction::TRIGGER_AUTOMATIC,
            $metadata[
                'submission_trigger'
            ] ?? null,
        );

        /*
         * ...while the successful retry records Manual.
         */
        $this->assertSame(
            SubmitShipmentToProviderAction::TRIGGER_MANUAL,
            $metadata[
                'last_submission_trigger'
            ] ?? null,
        );

        /*
         * Provider response metadata must survive the audit
         * metadata merge introduced in 8.8F.3.
         */
        $this->assertSame(
            'provider-value',
            $metadata[
                'provider_metadata'
            ] ?? null,
        );

        $this->assertSame(
            2,
            $provider->createCount,
        );
    }

    private function providerAccount(): ShippingProviderAccount
    {
        $admin =
            Admin::factory()
                ->create();

        $manager =
            app(
                ShippingProviderAccountManager::class,
            );

        $account =
            $manager->configure(
                providerCode: ShippingProviderCode::Pathao,

                name: 'Failure Safety Pathao',

                credentials: [
                    'test' => 'credential',
                ],

                settings: [],

                actor: $admin,
            );

        $account =
            $manager->markConnected(
                $account,
                $admin,
            );

        return $manager
            ->updateOperationalSettings(
                account: $account,

                isActive: true,

                autoSubmit: true,

                isDefault: false,

                actor: $admin,
            );
    }

    private function shipment(
        ShippingProviderAccount $account,
    ): Shipment {
        $user =
            User::factory()
                ->create();

        /*
         * The checkout delivery service intentionally remains
         * independent from the physical Pathao courier.
         */
        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => ManualShippingProvider::CODE,

                    'is_active' => true,
                ]);

        $order =
            Order::factory()
                ->for(
                    $user,
                )
                ->create([
                    'status' => OrderStatus::Processing,

                    'fulfilment_status' => FulfilmentStatus::Processing,

                    'shipping_method_id' => $shippingMethod->id,

                    'shipping_method_code' => $shippingMethod->code,

                    'shipping_method_name' => $shippingMethod->name,
                ]);

        return Shipment::factory()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'status' => ShipmentStatus::Pending,

                'provider_code' => ShippingProviderCode::Pathao
                    ->value,

                'shipping_provider_account_id' => $account->id,
            ]);
    }

    private function registerProvider(
        ShippingProvider $provider,
    ): void {
        $registry =
            new ShippingProviderRegistry;

        $registry->register(
            new ManualShippingProvider,
        );

        $registry->register(
            $provider,
        );

        $this->app->instance(
            ShippingProviderRegistry::class,
            $registry,
        );
    }
}

final class FakeRejectedAutoSubmitProvider implements ShippingProvider
{
    public int $createCount = 0;

    public function code(): string
    {
        return ShippingProviderCode::Pathao
            ->value;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        $this->createCount++;

        throw ValidationException::withMessages([
            'provider' => 'The test provider rejected this parcel.',
        ]);
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        return new ProviderShipmentTrackingResult(
            providerStatus: null,
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}

final class FakeAmbiguousAutoSubmitProvider implements ShippingProvider
{
    public int $createCount = 0;

    public function code(): string
    {
        return ShippingProviderCode::Pathao
            ->value;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        $this->createCount++;

        throw new ProviderSubmissionOutcomeUnknownException(
            'The test provider outcome is intentionally ambiguous.',
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        return new ProviderShipmentTrackingResult(
            providerStatus: null,
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}

final class FakeRecoveringAutoSubmitProvider implements ShippingProvider
{
    public int $createCount = 0;

    public function code(): string
    {
        return ShippingProviderCode::Pathao
            ->value;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        $this->createCount++;

        if (
            $this->createCount ===
            1
        ) {
            throw ValidationException::withMessages([
                'provider' => 'The first test provider request is rejected.',
            ]);
        }

        return new ProviderShipmentSubmissionResult(
            externalReference: 'RECOVERED-REFERENCE-123',

            carrier: 'Recovered Courier',

            service: 'Recovered Service',

            trackingNumber: 'RECOVERED-TRACK-123',

            trackingUrl: 'https://tracking.example.test/RECOVERED-TRACK-123',

            metadata: [
                'provider_metadata' => 'provider-value',
            ],
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        return new ProviderShipmentTrackingResult(
            providerStatus: null,
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}
