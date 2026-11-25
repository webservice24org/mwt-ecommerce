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
            'merchant_product_data',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string(
                        'item_key',
                        100,
                    );

                $table
                    ->foreignId(
                        'product_id',
                    )
                    ->constrained(
                        'products',
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'product_variant_id',
                    )
                    ->nullable()
                    ->constrained(
                        'product_variants',
                    )
                    ->cascadeOnDelete();

                $table
                    ->string(
                        'condition',
                        20,
                    )
                    ->default('new');

                $table
                    ->string(
                        'identifier_state',
                        40,
                    )
                    ->default('unknown');

                $table
                    ->string(
                        'gtin',
                        14,
                    )
                    ->nullable();

                $table
                    ->string(
                        'mpn',
                        70,
                    )
                    ->nullable();

                $table
                    ->string(
                        'google_product_category',
                        255,
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'version',
                    )
                    ->default(1);

                $table
                    ->unsignedBigInteger(
                        'updated_by_admin_id',
                    )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->dateTime(
                    'updated_at',
                );

                $table->unique(
                    'item_key',
                    'merchant_product_data_item_key_uq',
                );

                $table->index(
                    [
                        'product_id',
                        'product_variant_id',
                    ],
                    'merchant_product_data_entity_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_product_data',
        );
    }
};
