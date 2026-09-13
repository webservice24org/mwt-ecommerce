<?php

declare(strict_types=1);

use App\Domain\Catalog\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name', 180);

            $table->string('slug', 200)
                ->unique();

            $table->text('short_description')
                ->nullable();

            $table->longText('description')
                ->nullable();

            $table->string('status', 30)
                ->default(ProductStatus::Draft->value)
                ->index();

            $table->boolean('is_featured')
                ->default(false)
                ->index();

            $table->unsignedInteger('position')
                ->default(0);

            $table->timestamp('published_at')
                ->nullable()
                ->index();

            $table->string('meta_title', 180)
                ->nullable();

            $table->string('meta_description', 320)
                ->nullable();

            $table->timestamps();

            $table->index([
                'status',
                'published_at',
            ]);

            $table->index([
                'brand_id',
                'status',
            ]);

            $table->index([
                'is_featured',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
