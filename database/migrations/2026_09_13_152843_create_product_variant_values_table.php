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
            'product_variant_values',
            function (Blueprint $table): void {
                $table
                    ->foreignId('product_variant_id')
                    ->constrained('product_variants')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('attribute_value_id')
                    ->constrained('attribute_values')
                    ->restrictOnDelete();

                $table->timestamps();

                $table->primary(
                    [
                        'product_variant_id',
                        'attribute_value_id',
                    ],
                    'pvv_primary',
                );

                $table->index(
                    [
                        'attribute_value_id',
                        'product_variant_id',
                    ],
                    'pvv_attr_variant_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_variant_values',
        );
    }
};
