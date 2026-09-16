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
            'product_videos',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('product_id')
                    ->unique()
                    ->constrained('products')
                    ->cascadeOnDelete();

                $table
                    ->string('type', 30);

                /*
                 * Used for uploaded videos.
                 */
                $table
                    ->string('path')
                    ->nullable();

                /*
                 * Used for YouTube/Vimeo.
                 */
                $table
                    ->text('url')
                    ->nullable();

                $table
                    ->string('original_name', 255)
                    ->nullable();

                $table
                    ->string('mime_type', 100)
                    ->nullable();

                $table
                    ->unsignedBigInteger('file_size')
                    ->nullable();

                $table
                    ->string('title', 180)
                    ->nullable();

                $table->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_videos',
        );
    }
};
