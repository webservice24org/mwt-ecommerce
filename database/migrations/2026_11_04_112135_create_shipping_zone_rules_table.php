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
            'shipping_zone_rules',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('shipping_zone_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('type', 40);

                $table->string('value', 190);

                $table->timestamps();

                $table->index([
                    'shipping_zone_id',
                    'type',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shipping_zone_rules',
        );
    }
};
