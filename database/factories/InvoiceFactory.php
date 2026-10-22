<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),

            'invoice_number' => 'INV-'
                .now()->format('Ymd')
                .'-'
                .Str::upper(
                    Str::random(8),
                ),

            'customer_name' => fake()->name(),

            'customer_email' => fake()->safeEmail(),

            'issued_at' => now(),
        ];
    }
}
