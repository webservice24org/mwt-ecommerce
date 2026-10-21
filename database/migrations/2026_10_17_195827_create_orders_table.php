<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             * Optional reference to the customer's saved address.
             *
             * The snapshot fields below are authoritative historical
             * order data. Deleting a saved address must never destroy
             * an order.
             */
            $table
                ->foreignId('delivery_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();

            $table
                ->string('order_number', 50)
                ->unique();

            /*
             * String columns are intentional in 6.4A.
             * Typed enums/casts are introduced in 6.4B.
             */
            $table
                ->string('status', 50)
                ->default('pending');

            $table
                ->string('payment_method', 50)
                ->default('cash_on_delivery');

            $table
                ->string('payment_status', 50)
                ->default('unpaid');

            $table
                ->string('fulfilment_status', 50)
                ->default('unfulfilled');

            $table
                ->char('currency_code', 3)
                ->default('BDT');

            /*
             * Monetary values use integer minor units.
             */
            $table
                ->unsignedBigInteger('subtotal')
                ->default(0);

            $table
                ->unsignedBigInteger('discount_total')
                ->default(0);

            $table
                ->unsignedBigInteger('delivery_total')
                ->default(0);

            $table
                ->unsignedBigInteger('tax_total')
                ->default(0);

            $table
                ->unsignedBigInteger('grand_total')
                ->default(0);

            /*
             * Immutable delivery-address snapshot.
             */
            $table->string(
                'delivery_recipient_name',
                150,
            );

            $table->string(
                'delivery_phone',
                30,
            );

            $table->string(
                'delivery_address_line_1',
                255,
            );

            $table
                ->string(
                    'delivery_address_line_2',
                    255,
                )
                ->nullable();

            $table->string(
                'delivery_city',
                120,
            );

            $table
                ->string(
                    'delivery_state_or_region',
                    120,
                )
                ->nullable();

            $table
                ->string(
                    'delivery_postal_code',
                    30,
                )
                ->nullable();

            $table->char(
                'delivery_country_code',
                2,
            );

            $table
                ->timestamp('placed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'created_at',
            ]);

            $table->index([
                'status',
                'created_at',
            ]);

            $table->index([
                'payment_status',
                'created_at',
            ]);

            $table->index([
                'fulfilment_status',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'orders',
        );
    }
};
