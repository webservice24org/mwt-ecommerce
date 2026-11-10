<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('shipping_method_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 120);
            $table->string('code', 80);

            $table->unsignedBigInteger('amount')
                ->default(0);

            $table->unsignedBigInteger('min_order_amount')
                ->nullable();

            $table->unsignedBigInteger('max_order_amount')
                ->nullable();

            $table->unsignedBigInteger('free_shipping_threshold')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('priority')
                ->default(0);

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'shipping_method_id',
                'code',
            ]);

            $table->index([
                'is_active',
                'priority',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
