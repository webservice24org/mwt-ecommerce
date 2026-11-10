<?php

declare(strict_types=1);

use App\Domain\Shipping\Enums\ShipmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('shipping_method_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('status', 32)
                ->default(
                    ShipmentStatus::Pending->value,
                )
                ->index();

            $table->string('carrier', 120)
                ->nullable();

            $table->string('service', 120)
                ->nullable();

            $table->string('tracking_number', 190)
                ->nullable()
                ->index();

            $table->string('tracking_url', 2048)
                ->nullable();

            $table->timestamp('shipped_at')
                ->nullable();

            $table->timestamp('delivered_at')
                ->nullable();

            $table->timestamp('returned_at')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->index([
                'order_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shipments',
        );
    }
};
