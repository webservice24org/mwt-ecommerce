<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 150);

            $table
                ->string('slug', 180)
                ->unique();

            $table->text('description')->nullable();

            $table
                ->string('logo_path')
                ->nullable();

            $table
                ->boolean('is_active')
                ->default(true)
                ->index();

            $table
                ->unsignedInteger('position')
                ->default(0);

            $table
                ->string('meta_title', 180)
                ->nullable();

            $table
                ->string('meta_description', 320)
                ->nullable();

            $table->timestamps();

            $table->index([
                'is_active',
                'position',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
