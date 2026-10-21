<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('label', 50)
                ->nullable();

            $table->string('recipient_name', 150);

            $table->string('phone', 30);

            $table->string('address_line_1', 255);

            $table->string('address_line_2', 255)
                ->nullable();

            $table->string('city', 120);

            $table->string('state_or_region', 120)
                ->nullable();

            $table->string('postal_code', 30)
                ->nullable();

            $table->char('country_code', 2)
                ->default('BD');

            $table->boolean('is_default')
                ->default(false);

            $table->timestamps();

            $table->index([
                'user_id',
                'is_default',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'addresses',
        );
    }
};
