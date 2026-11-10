<?php

declare(strict_types=1);

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerManualShipmentTrackingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_see_manually_assigned_courier_without_tracking_details(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'Sundarban Courier',
            'service' => null,
            'tracking_number' => null,
            'tracking_url' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Show')
                    ->has('order.shipments', 1)
                    ->where('order.shipments.0.provider.code', 'manual')
                    ->where('order.shipments.0.carrier', 'Sundarban Courier')
                    ->where('order.shipments.0.service', null)
                    ->where('order.shipments.0.tracking_number', null)
                    ->where('order.shipments.0.tracking_url', null)
            );
    }

    public function test_customer_can_see_manual_tracking_number_without_tracking_url(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'SA Paribahan',
            'service' => 'Home Delivery',
            'tracking_number' => 'MANUAL-10001',
            'tracking_url' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Show')
                    ->has('order.shipments', 1)
                    ->where('order.shipments.0.provider.code', 'manual')
                    ->where('order.shipments.0.carrier', 'SA Paribahan')
                    ->where('order.shipments.0.service', 'Home Delivery')
                    ->where('order.shipments.0.tracking_number', 'MANUAL-10001')
                    ->where('order.shipments.0.tracking_url', null)
            );
    }

    public function test_customer_receives_manual_tracking_url_when_available(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => 'RedX',
            'service' => 'Standard',
            'tracking_number' => 'RX-778899',
            'tracking_url' => 'https://example.test/track/RX-778899',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Show')
                    ->has('order.shipments', 1)
                    ->where('order.shipments.0.provider.code', 'manual')
                    ->where('order.shipments.0.carrier', 'RedX')
                    ->where('order.shipments.0.service', 'Standard')
                    ->where('order.shipments.0.tracking_number', 'RX-778899')
                    ->where(
                        'order.shipments.0.tracking_url',
                        'https://example.test/track/RX-778899',
                    )
            );
    }

    public function test_customer_order_page_handles_shipment_without_courier_information(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => null,
            'carrier' => null,
            'service' => null,
            'tracking_number' => null,
            'tracking_url' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Show')
                    ->has('order.shipments', 1)
                    ->where('order.shipments.0.provider.code', 'manual')
                    ->where('order.shipments.0.carrier', null)
                    ->where('order.shipments.0.service', null)
                    ->where('order.shipments.0.tracking_number', null)
                    ->where('order.shipments.0.tracking_url', null)
            );
    }

    public function test_provider_generated_tracking_remains_visible_to_customer(): void
    {
        [$customer, $order] = $this->customerOrder();

        Shipment::factory()->for($order)->create([
            'provider_code' => 'pathao',
            'carrier' => 'Pathao',
            'service' => 'Parcel',
            'tracking_number' => 'PATHAO-CONSIGNMENT-123',
            'tracking_url' => 'https://merchant.pathao.com/tracking/PATHAO-CONSIGNMENT-123',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Customer/Orders/Show')
                    ->has('order.shipments', 1)
                    ->where('order.shipments.0.provider.code', 'pathao')
                    ->where('order.shipments.0.carrier', 'Pathao')
                    ->where('order.shipments.0.service', 'Parcel')
                    ->where('order.shipments.0.tracking_number', 'PATHAO-CONSIGNMENT-123')
                    ->where(
                        'order.shipments.0.tracking_url',
                        'https://merchant.pathao.com/tracking/PATHAO-CONSIGNMENT-123',
                    )
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
