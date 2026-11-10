<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\SubmitShipmentToProviderAction;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class SubmitShipmentToProviderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_provider_submission_persists_reference_and_tracking(): void
    {
        $provider = new FakeExternalShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: $provider->code(),
        );

        $submission = app(
            SubmitShipmentToProviderAction::class,
        )->execute(
            $shipment,
        );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitted,
            $submission->status,
        );

        $this->assertSame(
            'FAKE-1',
            $submission->external_reference,
        );

        $this->assertSame(
            1,
            $submission->attempt_count,
        );

        $this->assertNotSame(
            '',
            $submission->idempotency_key,
        );

        $this->assertNotNull(
            $submission->submitted_at,
        );

        $this->assertSame(
            1,
            $provider->createCalls,
        );

        $this->assertSame(
            $submission->idempotency_key,
            $provider->idempotencyKeys[0],
        );

        $shipment->refresh();

        $this->assertSame(
            'Fake Courier',
            $shipment->carrier,
        );

        $this->assertSame(
            'Express',
            $shipment->service,
        );

        $this->assertSame(
            'TRACK-FAKE-1',
            $shipment->tracking_number,
        );

        $this->assertSame(
            'https://tracking.example.test/FAKE-1',
            $shipment->tracking_url,
        );
    }

    public function test_successful_submission_is_idempotent_and_does_not_call_provider_twice(): void
    {
        $provider = new FakeExternalShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: $provider->code(),
        );

        $action = app(
            SubmitShipmentToProviderAction::class,
        );

        $first = $action->execute(
            $shipment,
        );

        $second = $action->execute(
            $shipment->refresh(),
        );

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertSame(
            $first->idempotency_key,
            $second->idempotency_key,
        );

        $this->assertSame(
            1,
            $second->attempt_count,
        );

        $this->assertSame(
            1,
            $provider->createCalls,
        );

        $this->assertDatabaseCount(
            'shipment_provider_submissions',
            1,
        );
    }

    public function test_submitted_provider_record_remains_idempotent_after_shipment_advances(): void
    {
        $provider = new FakeExternalShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: $provider->code(),
        );

        $action = app(
            SubmitShipmentToProviderAction::class,
        );

        $first = $action->execute(
            $shipment,
        );

        $shipment->update([
            'status' => ShipmentStatus::Shipped,
        ]);

        $second = $action->execute(
            $shipment->refresh(),
        );

        $this->assertSame(
            $first->id,
            $second->id,
        );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitted,
            $second->status,
        );

        $this->assertSame(
            1,
            $provider->createCalls,
        );
    }

    public function test_failed_retry_reuses_same_idempotency_key(): void
    {
        $provider = new FakeExternalShippingProvider(
            failuresRemaining: 1,
        );

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: $provider->code(),
        );

        $action = app(
            SubmitShipmentToProviderAction::class,
        );

        try {
            $action->execute(
                $shipment,
            );

            $this->fail(
                'The first provider submission should fail.',
            );
        } catch (
            RuntimeException
        ) {
            //
        }

        $failed =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Failed,
            $failed->status,
        );

        $this->assertSame(
            1,
            $failed->attempt_count,
        );

        $this->assertSame(
            'Provider shipment submission failed.',
            $failed->last_error,
        );

        $originalKey =
            $failed->idempotency_key;

        $successful = $action->execute(
            $shipment->refresh(),
        );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitted,
            $successful->status,
        );

        $this->assertSame(
            2,
            $successful->attempt_count,
        );

        $this->assertSame(
            $originalKey,
            $successful->idempotency_key,
        );

        $this->assertSame(
            [
                $originalKey,
                $originalKey,
            ],
            $provider->idempotencyKeys,
        );
    }

    public function test_manual_shipment_does_not_create_provider_submission(): void
    {
        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: 'manual',
        );

        try {
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'Manual shipment should not be submitted externally.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'provider',
                $exception->errors(),
            );
        }

        $this->assertDatabaseMissing(
            'shipment_provider_submissions',
            [
                'shipment_id' => $shipment->id,
            ],
        );
    }

    public function test_terminal_shipment_cannot_be_submitted(): void
    {
        $provider = new FakeExternalShippingProvider;

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: $provider->code(),

            status: ShipmentStatus::Shipped,
        );

        try {
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'Shipped shipment should not be submitted.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'shipment_status',
                $exception->errors(),
            );
        }

        $this->assertSame(
            0,
            $provider->createCalls,
        );
    }

    public function test_invalid_provider_tracking_url_fails_without_persisting_unsafe_url(): void
    {
        $provider =
            new FakeExternalShippingProvider(
                trackingUrl: 'javascript:alert(1)',
            );

        app(
            ShippingProviderRegistry::class,
        )->register(
            $provider,
        );

        [
            'shipment' => $shipment,
        ] = $this->shipmentFixture(
            driver: $provider->code(),
        );

        try {
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment,
            );

            $this->fail(
                'Unsafe provider tracking URL should fail.',
            );
        } catch (
            \UnexpectedValueException
        ) {
            //
        }

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

        $this->assertNull(
            $shipment
                ->refresh()
                ->tracking_url,
        );
    }

    public function test_submission_snapshot_rejects_provider_driver_change_after_failure(): void
    {
        $firstProvider =
            new FakeExternalShippingProvider(
                code: 'fake-one',

                failuresRemaining: 1,
            );

        $secondProvider =
            new FakeExternalShippingProvider(
                code: 'fake-two',
            );

        $registry = app(
            ShippingProviderRegistry::class,
        );

        $registry->register(
            $firstProvider,
        );

        $registry->register(
            $secondProvider,
        );

        [
            'shipment' => $shipment,

            'shippingMethod' => $shippingMethod,
        ] = $this->shipmentFixture(
            driver: $firstProvider->code(),
        );

        try {
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment,
            );
        } catch (
            RuntimeException
        ) {
            //
        }

        $shippingMethod->update([
            'driver' => $secondProvider->code(),
        ]);

        try {
            app(
                SubmitShipmentToProviderAction::class,
            )->execute(
                $shipment->refresh(),
            );

            $this->fail(
                'Provider snapshot should prevent changing the submission provider.',
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertArrayHasKey(
                'provider',
                $exception->errors(),
            );
        }

        $submission =
            ShipmentProviderSubmission::query()
                ->where(
                    'shipment_id',
                    $shipment->id,
                )
                ->firstOrFail();

        $this->assertSame(
            'fake-one',
            $submission->provider_code,
        );

        $this->assertSame(
            0,
            $secondProvider->createCalls,
        );
    }

    /**
     * @return array{
     *     shipment: Shipment,
     *     shippingMethod: ShippingMethod
     * }
     */
    private function shipmentFixture(
        string $driver,
        ShipmentStatus $status = ShipmentStatus::Pending,
    ): array {
        $user = User::factory()
            ->create();

        $shippingMethod =
            ShippingMethod::factory()
                ->create([
                    'driver' => $driver,

                    'is_active' => true,
                ]);

        $order = Order::factory()
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

        $shipment = Shipment::factory()
            ->for(
                $order,
            )
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'status' => $status,
            ]);

        return [
            'shipment' => $shipment,

            'shippingMethod' => $shippingMethod,
        ];
    }
}

final class FakeExternalShippingProvider implements ShippingProvider
{
    public int $createCalls = 0;

    /**
     * @var list<string>
     */
    public array $idempotencyKeys = [];

    public function __construct(
        private readonly string $code = 'fake',
        private int $failuresRemaining = 0,
        private readonly string $trackingUrl = 'https://tracking.example.test/FAKE-1',
    ) {}

    public function code(): string
    {
        return $this->code;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        $this->createCalls++;

        $this->idempotencyKeys[] =
            $idempotencyKey;

        if ($this->failuresRemaining > 0) {
            $this->failuresRemaining--;

            throw new RuntimeException(
                'Simulated provider failure with secret-token-that-must-not-be-stored.',
            );
        }

        return new ProviderShipmentSubmissionResult(
            externalReference: 'FAKE-1',

            carrier: 'Fake Courier',

            service: 'Express',

            trackingNumber: 'TRACK-FAKE-1',

            trackingUrl: $this->trackingUrl,

            metadata: [
                'remote_status' => 'created',
            ],
        );
    }

    public function refreshTracking(
        Shipment $shipment,
        string $externalReference,
    ): ProviderShipmentTrackingResult {
        return new ProviderShipmentTrackingResult(
            providerStatus: 'created',
        );
    }

    public function mapTrackingStatus(
        ?string $providerStatus,
    ): ?ShipmentStatus {
        return null;
    }
}
