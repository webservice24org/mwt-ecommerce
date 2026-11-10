<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\AutoSubmitShipmentIfEnabledAction;
use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Domain\Shipping\Data\ProviderShipmentSubmissionResult;
use App\Domain\Shipping\Data\ProviderShipmentTrackingResult;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
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
use Tests\TestCase;

final class AutoSubmitShipmentIfEnabledActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_shipment_is_never_auto_submitted(): void
    {
        $shipment = $this->shipment(
            providerCode: ShippingProviderCode::Manual,
            accountId: null,
        );

        $result = app(
            AutoSubmitShipmentIfEnabledAction::class,
        )->execute(
            $shipment,
        );

        $this->assertNull($result);

        $this->assertDatabaseMissing(
            'shipment_provider_submissions',
            [
                'shipment_id' => $shipment->id,
            ],
        );
    }

    public function test_external_provider_with_auto_submit_disabled_is_not_submitted(): void
    {
        $account = $this->providerAccount(
            autoSubmit: false,
        );

        $provider = new FakeAutoSubmitPathaoProvider;

        $this->registerOnly($provider);

        $shipment = $this->shipment(
            providerCode: ShippingProviderCode::Pathao,
            accountId: $account->id,
        );

        $result = app(
            AutoSubmitShipmentIfEnabledAction::class,
        )->execute(
            $shipment,
        );

        $this->assertNull($result);
        $this->assertSame(0, $provider->createCount);

        $this->assertDatabaseMissing(
            'shipment_provider_submissions',
            [
                'shipment_id' => $shipment->id,
            ],
        );
    }

    public function test_operational_external_provider_with_auto_submit_enabled_is_submitted(): void
    {
        $account = $this->providerAccount(
            autoSubmit: true,
        );

        $provider = new FakeAutoSubmitPathaoProvider;

        $this->registerOnly($provider);

        $shipment = $this->shipment(
            providerCode: ShippingProviderCode::Pathao,
            accountId: $account->id,
        );

        $submission = app(
            AutoSubmitShipmentIfEnabledAction::class,
        )->execute(
            $shipment,
        );

        $this->assertInstanceOf(
            ShipmentProviderSubmission::class,
            $submission,
        );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitted,
            $submission->status,
        );

        $this->assertSame(
            'AUTO-REFERENCE-123',
            $submission->external_reference,
        );

        $this->assertSame(1, $provider->createCount);

        $shipment->refresh();

        $this->assertSame(
            'Test Courier',
            $shipment->carrier,
        );

        $this->assertSame(
            'AUTO-TRACK-123',
            $shipment->tracking_number,
        );

        $this->assertSame(
            'https://tracking.example.test/AUTO-TRACK-123',
            $shipment->tracking_url,
        );
    }

    private function providerAccount(
        bool $autoSubmit,
    ): ShippingProviderAccount {
        $admin = Admin::factory()->create();

        $manager = app(
            ShippingProviderAccountManager::class,
        );

        $account = $manager->configure(
            providerCode: ShippingProviderCode::Pathao,
            name: 'Auto-submit Pathao',
            credentials: [
                'test' => 'credential',
            ],
            settings: [],
            actor: $admin,
        );

        $account = $manager->markConnected(
            $account,
            $admin,
        );

        return $manager->updateOperationalSettings(
            account: $account,
            isActive: true,
            autoSubmit: $autoSubmit,
            isDefault: false,
            actor: $admin,
        );
    }

    private function shipment(
        ShippingProviderCode $providerCode,
        ?int $accountId,
    ): Shipment {
        $user = User::factory()->create();

        /*
         * Checkout delivery service intentionally remains
         * independent from the physical courier provider.
         */
        $shippingMethod = ShippingMethod::factory()
            ->create([
                'driver' => ManualShippingProvider::CODE,
                'is_active' => true,
            ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,
                'fulfilment_status' => FulfilmentStatus::Processing,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method_code' => $shippingMethod->code,
                'shipping_method_name' => $shippingMethod->name,
            ]);

        return Shipment::factory()
            ->for($order)
            ->for(
                $shippingMethod,
                'shippingMethod',
            )
            ->create([
                'status' => ShipmentStatus::Pending,
                'provider_code' => $providerCode->value,
                'shipping_provider_account_id' => $accountId,
            ]);
    }

    private function registerOnly(
        ShippingProvider $provider,
    ): void {
        $registry = new ShippingProviderRegistry;

        $registry->register(
            new ManualShippingProvider,
        );

        $registry->register($provider);

        $this->app->instance(
            ShippingProviderRegistry::class,
            $registry,
        );
    }
}

final class FakeAutoSubmitPathaoProvider implements ShippingProvider
{
    public int $createCount = 0;

    public function code(): string
    {
        return ShippingProviderCode::Pathao->value;
    }

    public function createShipment(
        Shipment $shipment,
        string $idempotencyKey,
    ): ProviderShipmentSubmissionResult {
        $this->createCount++;

        return new ProviderShipmentSubmissionResult(
            externalReference: 'AUTO-REFERENCE-123',
            carrier: 'Test Courier',
            service: 'Test Service',
            trackingNumber: 'AUTO-TRACK-123',
            trackingUrl: 'https://tracking.example.test/AUTO-TRACK-123',
            metadata: [
                'idempotency_key' => $idempotencyKey,
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
