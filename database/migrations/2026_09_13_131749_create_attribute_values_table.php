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
            'attribute_values',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('attribute_id')
                    ->constrained('attributes')
                    ->cascadeOnDelete();

                $table->string('name', 120);

                $table->string('slug', 150);

                $table->unsignedInteger('position')
                    ->default(0);

                $table->boolean('is_active')
                    ->default(true)
                    ->index();

                $table->timestamps();

                $table->unique([
                    'attribute_id',
                    'slug',
                ]);

                $table->index([
                    'attribute_id',
                    'is_active',
                    'position',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'attribute_values',
        );
    }
};
