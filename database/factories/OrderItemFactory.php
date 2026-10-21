<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()
            ->numberBetween(
                1000,
                50000,
            );

        $quantity = fake()
            ->numberBetween(
                1,
                5,
            );

        return [
            'order_id' => Order::factory(),

            'product_id' => null,
            'variant_id' => null,

            'product_name' => fake()->words(
                3,
                true,
            ),

            'sku' => strtoupper(
                fake()->bothify(
                    'SKU-####-??',
                ),
            ),

            'variant_description' => null,

            'unit_price' => $unitPrice,

            'quantity' => $quantity,

            'line_total' => $unitPrice *
                $quantity,
        ];
    }
}
