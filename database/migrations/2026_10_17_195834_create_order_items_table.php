<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'order_items',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('order_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * Catalog references are nullable historical links.
                 * Order snapshots must survive catalog deletion.
                 */
                $table
                    ->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete();

                $table
                    ->foreignId('variant_id')
                    ->nullable()
                    ->constrained(
                        'product_variants',
                    )
                    ->nullOnDelete();

                /*
                 * Immutable catalog snapshot.
                 */
                $table->string(
                    'product_name',
                    255,
                );

                $table
                    ->string(
                        'sku',
                        150,
                    )
                    ->nullable();

                $table
                    ->string(
                        'variant_description',
                        255,
                    )
                    ->nullable();

                $table->unsignedBigInteger(
                    'unit_price',
                );

                $table->unsignedInteger(
                    'quantity',
                );

                $table->unsignedBigInteger(
                    'line_total',
                );

                $table->timestamps();

                $table->index([
                    'order_id',
                    'product_id',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'order_items',
        );
    }
};
