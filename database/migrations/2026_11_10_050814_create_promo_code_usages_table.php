<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_code_usages', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('promo_code_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Guest checkout may not have a user.
             */
            $table
                ->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table
                ->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Snapshot of the normalized promo code.
             * Historical usage remains understandable even
             * if the display metadata changes later.
             */
            $table->string('code', 100);

            /*
             * Exact discount granted to this order.
             */
            $table->unsignedBigInteger('discount_amount');

            $table->timestamp('used_at');

            $table->timestamps();

            /*
             * One order cannot redeem the same promo twice.
             */
            $table->unique([
                'promo_code_id',
                'order_id',
            ]);

            $table->index([
                'promo_code_id',
                'user_id',
            ]);

            $table->index([
                'promo_code_id',
                'used_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
    }
};
