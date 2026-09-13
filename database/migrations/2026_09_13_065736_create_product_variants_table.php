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
            'product_variants',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('product_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('sku', 100)
                    ->unique();

                $table->string('name', 180)
                    ->nullable();

                $table->unsignedBigInteger('price');

                $table->unsignedBigInteger('compare_at_price')
                    ->nullable();

                $table->unsignedBigInteger('cost_price')
                    ->nullable();

                $table->string('barcode', 100)
                    ->nullable()
                    ->unique();

                $table->unsignedInteger('position')
                    ->default(0);

                $table->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->boolean('is_default')
                    ->default(false)
                    ->index();

                $table->decimal(
                    'weight',
                    10,
                    3,
                )->nullable();

                $table->timestamps();

                $table->index([
                    'product_id',
                    'is_active',
                    'position',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_variants',
        );
    }
};
