<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
final class ShipmentFactory extends Factory
{
    protected $model =
        Shipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),

            'shipping_method_id' => ShippingMethod::factory(),

            'status' => ShipmentStatus::Pending,

            'carrier' => null,

            'service' => null,

            'tracking_number' => null,

            'tracking_url' => null,

            'shipped_at' => null,

            'delivered_at' => null,

            'returned_at' => null,

            'metadata' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ShipmentStatus::Processing,
            ],
        );
    }

    public function shipped(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ShipmentStatus::Shipped,

                'shipped_at' => now(),
            ],
        );
    }

    public function delivered(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ShipmentStatus::Delivered,

                'shipped_at' => now()->subDay(),

                'delivered_at' => now(),
            ],
        );
    }

    public function returned(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ShipmentStatus::Returned,

                'shipped_at' => now()
                    ->subDays(3),

                'delivered_at' => now()
                    ->subDays(2),

                'returned_at' => now(),
            ],
        );
    }

    public function cancelled(): static
    {
        return $this->state(
            fn (): array => [
                'status' => ShipmentStatus::Cancelled,

                'shipped_at' => null,

                'delivered_at' => null,

                'returned_at' => null,
            ],
        );
    }

    public function withTracking(): static
    {
        return $this->state(
            function (): array {
                $trackingNumber =
                    strtoupper(
                        fake()->bothify(
                            'MWT-########',
                        ),
                    );

                return [
                    'carrier' => 'Test Courier',

                    'service' => 'Standard',

                    'tracking_number' => $trackingNumber,

                    'tracking_url' => sprintf(
                        'https://tracking.example.test/%s',
                        $trackingNumber,
                    ),
                ];
            },
        );
    }
}
