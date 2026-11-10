<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ManualShipmentLifecycleCustomerStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_shipment_processing_state_is_visible_to_customer(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'Sundarban Courier',
            'service' => 'Home Delivery',
            'status' => 'processing',
            'tracking_number' => 'MANUAL-PROCESSING-001',
            'tracking_url' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'returned_at' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->has('order.shipments', 1)
                ->where('order.shipments.0.provider.code', 'manual')
                ->where('order.shipments.0.status.value', 'processing')
                ->where('order.shipments.0.carrier', 'Sundarban Courier')
                ->where('order.shipments.0.service', 'Home Delivery')
                ->where('order.shipments.0.tracking_number', 'MANUAL-PROCESSING-001')
                ->where('order.shipments.0.shipped_at', null)
                ->where('order.shipments.0.delivered_at', null)
                ->where('order.shipments.0.returned_at', null)
            );
    }

    public function test_manual_shipment_shipped_state_and_timestamp_are_visible_to_customer(): void
    {
        [$customer, $order] = $this->customerOrder();

        $shippedAt = now()->subMinutes(15)->startOfSecond();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'SA Paribahan',
            'service' => 'Parcel',
            'status' => 'shipped',
            'tracking_number' => 'MANUAL-SHIPPED-001',
            'tracking_url' => 'https://example.test/track/MANUAL-SHIPPED-001',
            'shipped_at' => $shippedAt,
            'delivered_at' => null,
            'returned_at' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.shipments.0.provider.code', 'manual')
                ->where('order.shipments.0.status.value', 'shipped')
                ->where('order.shipments.0.tracking_number', 'MANUAL-SHIPPED-001')
                ->where(
                    'order.shipments.0.tracking_url',
                    'https://example.test/track/MANUAL-SHIPPED-001',
                )
                ->where(
                    'order.shipments.0.shipped_at',
                    $shippedAt->toISOString(),
                )
                ->where('order.shipments.0.delivered_at', null)
                ->where('order.shipments.0.returned_at', null)
            );
    }

    public function test_manual_shipment_delivered_state_and_timestamp_are_visible_to_customer(): void
    {
        [$customer, $order] = $this->customerOrder();

        $shippedAt = now()->subDay()->startOfSecond();
        $deliveredAt = now()->subHour()->startOfSecond();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'RedX',
            'service' => 'Standard',
            'status' => 'delivered',
            'tracking_number' => 'MANUAL-DELIVERED-001',
            'tracking_url' => 'https://example.test/track/MANUAL-DELIVERED-001',
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
            'returned_at' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.shipments.0.provider.code', 'manual')
                ->where('order.shipments.0.status.value', 'delivered')
                ->where('order.shipments.0.tracking_number', 'MANUAL-DELIVERED-001')
                ->where(
                    'order.shipments.0.shipped_at',
                    $shippedAt->toISOString(),
                )
                ->where(
                    'order.shipments.0.delivered_at',
                    $deliveredAt->toISOString(),
                )
                ->where('order.shipments.0.returned_at', null)
            );
    }

    public function test_manual_shipment_returned_state_and_timestamp_are_visible_to_customer(): void
    {
        [$customer, $order] = $this->customerOrder();

        $shippedAt = now()->subDays(2)->startOfSecond();
        $deliveredAt = now()->subDay()->startOfSecond();
        $returnedAt = now()->subMinutes(30)->startOfSecond();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'Pathao Manual Desk',
            'service' => 'Manual Return',
            'status' => 'returned',
            'tracking_number' => 'MANUAL-RETURNED-001',
            'tracking_url' => 'https://example.test/track/MANUAL-RETURNED-001',
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
            'returned_at' => $returnedAt,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.shipments.0.provider.code', 'manual')
                ->where('order.shipments.0.status.value', 'returned')
                ->where('order.shipments.0.tracking_number', 'MANUAL-RETURNED-001')
                ->where(
                    'order.shipments.0.shipped_at',
                    $shippedAt->toISOString(),
                )
                ->where(
                    'order.shipments.0.delivered_at',
                    $deliveredAt->toISOString(),
                )
                ->where(
                    'order.shipments.0.returned_at',
                    $returnedAt->toISOString(),
                )
            );
    }

    public function test_manual_tracking_details_survive_lifecycle_transitions(): void
    {
        [$customer, $order] = $this->customerOrder();

        $shipment = Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'Sundarban Courier',
            'service' => 'Home Delivery',
            'status' => 'processing',
            'tracking_number' => 'MANUAL-PERSIST-001',
            'tracking_url' => 'https://example.test/track/MANUAL-PERSIST-001',
            'shipped_at' => null,
            'delivered_at' => null,
            'returned_at' => null,
        ]);

        $shippedAt = now()->subHour()->startOfSecond();

        $shipment->forceFill([
            'status' => 'shipped',
            'shipped_at' => $shippedAt,
        ])->save();

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.shipments.0.provider.code', 'manual')
                ->where('order.shipments.0.status.value', 'shipped')
                ->where('order.shipments.0.carrier', 'Sundarban Courier')
                ->where('order.shipments.0.service', 'Home Delivery')
                ->where('order.shipments.0.tracking_number', 'MANUAL-PERSIST-001')
                ->where(
                    'order.shipments.0.tracking_url',
                    'https://example.test/track/MANUAL-PERSIST-001',
                )
                ->where(
                    'order.shipments.0.shipped_at',
                    $shippedAt->toISOString(),
                )
            );
    }

    public function test_manual_shipment_lifecycle_does_not_require_provider_submission(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'Local Courier',
            'service' => 'Manual Delivery',
            'status' => 'shipped',
            'tracking_number' => 'LOCAL-001',
            'tracking_url' => null,
            'shipped_at' => now()->startOfSecond(),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.shipments.0.provider.code', 'manual')
                ->where('order.shipments.0.status.value', 'shipped')
                ->where('order.shipments.0.carrier', 'Local Courier')
                ->where('order.shipments.0.tracking_number', 'LOCAL-001')
                ->where('order.shipments.0.tracking_url', null)
            );
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function customerOrder(): array
    {
        $customer = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
        ]);

        return [$customer, $order];
    }
}
