<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 120);

            $table->string('code', 80)
                ->unique();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->boolean('is_catch_all')
                ->default(false);

            $table->unsignedInteger('priority')
                ->default(0);

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->index([
                'is_active',
                'priority',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
