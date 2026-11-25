<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table): void {
            $table->string('section', 80)->primary();
            $table->json('config');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
