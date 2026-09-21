<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('page_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 100);
            $table->string('template', 100);

            $table->json('config');

            $table->unsignedInteger('position')
                ->default(0);

            $table->boolean('is_enabled')
                ->default(true);

            $table->timestamps();

            $table->index([
                'page_id',
                'is_enabled',
                'position',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
