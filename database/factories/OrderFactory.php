<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()
            ->numberBetween(
                10000,
                100000,
            );

        return [
            'user_id' => User::factory(),
            'delivery_address_id' => null,

            'order_number' => 'ORD-'.fake()
                ->unique()
                ->numerify(
                    '##########',
                ),

            'status' => OrderStatus::Pending,

            'payment_method' => PaymentMethod::CashOnDelivery,

            'payment_status' => PaymentStatus::Unpaid,

            'fulfilment_status' => FulfilmentStatus::Unfulfilled,

            'currency_code' => 'BDT',

            'subtotal' => $subtotal,

            'discount_total' => 0,

            'delivery_total' => 0,

            'tax_total' => 0,

            'grand_total' => $subtotal,

            'delivery_recipient_name' => fake()->name(),

            'delivery_phone' => fake()->numerify(
                '01#########',
            ),

            'delivery_address_line_1' => fake()->streetAddress(),

            'delivery_address_line_2' => null,

            'delivery_city' => fake()->city(),

            'delivery_state_or_region' => fake()->state(),

            'delivery_postal_code' => fake()->postcode(),

            'delivery_country_code' => 'BD',

            'placed_at' => now(),
        ];
    }
}
