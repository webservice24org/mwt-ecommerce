<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
final class ShipmentItemFactory extends Factory
{
    protected $model =
        ShipmentItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),

            'order_item_id' => OrderItem::factory(),

            'quantity' => 1,
        ];
    }

    public function quantity(
        int $quantity,
    ): static {
        return $this->state(
            fn (): array => [
                'quantity' => $quantity,
            ],
        );
    }
}
