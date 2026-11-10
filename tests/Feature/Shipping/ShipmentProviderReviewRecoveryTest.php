<?php

declare(strict_types=1);

namespace Tests\Feature\Shipping;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shipping\Actions\ResolveShipmentProviderReviewAction;
use App\Domain\Shipping\Enums\ShipmentProviderReconciliationStatus;
use App\Domain\Shipping\Enums\ShipmentProviderReviewResolution;
use App\Domain\Shipping\Enums\ShipmentProviderSubmissionStatus;
use App\Domain\Shipping\Enums\ShipmentProviderTrackingSyncStatus;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentProviderSubmission;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ShipmentProviderReviewRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_confirm_existing_provider_shipment_without_changing_physical_state(): void
    {
        [
            'admin' => $admin,
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture();

        $originalKey = $submission->idempotency_key;

        $result = app(
            ResolveShipmentProviderReviewAction::class,
        )->execute(
            shipment: $shipment,
            resolution: ShipmentProviderReviewResolution::ConfirmExisting,
            externalReference: 'PATHAO-FOUND-100',
            note: 'Verified the consignment in the Pathao merchant panel.',
            actor: $admin,
        );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Submitted,
            $result->status,
        );

        $this->assertSame(
            'PATHAO-FOUND-100',
            $result->external_reference,
        );

        $this->assertSame(
            $originalKey,
            $result->idempotency_key,
        );

        $this->assertSame(
            ShipmentProviderTrackingSyncStatus::Pending,
            $result->tracking_sync_status,
        );

        $this->assertSame(
            ShipmentProviderReconciliationStatus::Pending,
            $result->reconciliation_status,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->refresh()->status,
        );

        $history = $result->metadata['recovery_history'] ?? null;

        $this->assertIsArray($history);
        $this->assertCount(1, $history);
        $this->assertSame(
            ShipmentProviderReviewResolution::ConfirmExisting->value,
            $history[0]['resolution'] ?? null,
        );
    }

    public function test_admin_can_allow_retry_only_after_confirming_no_provider_shipment_exists(): void
    {
        [
            'admin' => $admin,
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture();

        $originalKey = $submission->idempotency_key;

        $result = app(
            ResolveShipmentProviderReviewAction::class,
        )->execute(
            shipment: $shipment,
            resolution: ShipmentProviderReviewResolution::AllowRetry,
            externalReference: null,
            note: 'Checked Pathao and confirmed that no consignment exists.',
            actor: $admin,
        );

        $this->assertSame(
            ShipmentProviderSubmissionStatus::Failed,
            $result->status,
        );

        $this->assertNull(
            $result->external_reference,
        );

        $this->assertSame(
            $originalKey,
            $result->idempotency_key,
        );

        $this->assertSame(
            ShipmentStatus::Processing,
            $shipment->refresh()->status,
        );
    }

    public function test_non_review_submission_cannot_use_manual_recovery(): void
    {
        [
            'admin' => $admin,
            'shipment' => $shipment,
            'submission' => $submission,
        ] = $this->fixture();

        $submission->update([
            'status' => ShipmentProviderSubmissionStatus::Failed,
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(
            ResolveShipmentProviderReviewAction::class,
        )->execute(
            shipment: $shipment,
            resolution: ShipmentProviderReviewResolution::AllowRetry,
            externalReference: null,
            note: 'This should not be accepted.',
            actor: $admin,
        );
    }

    public function test_http_review_resolution_is_admin_only_and_preserves_nested_order_ownership(): void
    {
        [
            'admin' => $admin,
            'order' => $order,
            'shipment' => $shipment,
        ] = $this->fixture();

        $this
            ->post(
                route(
                    'admin.orders.shipments.provider.review-resolution',
                    [$order, $shipment],
                ),
                [
                    'resolution' => 'allow_retry',
                    'note' => 'Verified provider panel.',
                ],
            )
            ->assertRedirect(
                route('admin.login'),
            );

        $other = $this->fixture();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.orders.shipments.provider.review-resolution',
                    [$order, $other['shipment']],
                ),
                [
                    'resolution' => 'allow_retry',
                    'note' => 'Verified provider panel.',
                ],
            )
            ->assertNotFound();
    }

    /**
     * @return array{
     *     admin: Admin,
     *     order: Order,
     *     shipment: Shipment,
     *     submission: ShipmentProviderSubmission
     * }
     */
    private function fixture(): array
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $method = ShippingMethod::factory()->create([
            'driver' => 'pathao',
            'is_active' => true,
        ]);

        $order = Order::factory()
            ->for($user)
            ->create([
                'status' => OrderStatus::Processing,
                'fulfilment_status' => FulfilmentStatus::Processing,
                'shipping_method_id' => $method->id,
                'shipping_method_code' => $method->code,
                'shipping_method_name' => $method->name,
            ]);

        $shipment = Shipment::factory()
            ->for($order)
            ->for($method, 'shippingMethod')
            ->create([
                'status' => ShipmentStatus::Processing,
            ]);

        $submission = ShipmentProviderSubmission::query()->create([
            'shipment_id' => $shipment->id,
            'provider_code' => 'pathao',
            'idempotency_key' => (string) Str::uuid(),
            'status' => ShipmentProviderSubmissionStatus::ReviewRequired,
            'external_reference' => null,
            'attempt_count' => 1,
            'last_attempted_at' => now(),
            'submitted_at' => null,
            'last_error' => 'Provider submission outcome is uncertain. Manual provider review is required before retry.',
            'tracking_sync_status' => ShipmentProviderTrackingSyncStatus::Pending,
            'tracking_sync_attempt_count' => 0,
            'provider_status' => null,
            'provider_mapped_shipment_status' => null,
            'reconciliation_status' => ShipmentProviderReconciliationStatus::Pending,
            'reconciliation_attempt_count' => 0,
            'metadata' => null,
        ]);

        return compact('admin', 'order', 'shipment', 'submission');
    }
}
