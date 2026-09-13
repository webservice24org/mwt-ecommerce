<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 120);

            $table->string('slug', 150)
                ->unique();

            $table->unsignedInteger('position')
                ->default(0);

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->index([
                'is_active',
                'position',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
