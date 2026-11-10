<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->timestamps();

            $table->unique([
                'shipment_id',
                'order_item_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shipment_items',
        );
    }
};
