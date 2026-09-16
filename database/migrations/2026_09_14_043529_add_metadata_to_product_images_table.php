<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'product_images',
            function (Blueprint $table): void {
                $table
                    ->string('original_name', 255)
                    ->nullable()
                    ->after('path');

                $table
                    ->string('mime_type', 100)
                    ->nullable()
                    ->after('original_name');

                $table
                    ->unsignedBigInteger('file_size')
                    ->nullable()
                    ->after('mime_type');

                $table
                    ->unsignedInteger('width')
                    ->nullable()
                    ->after('file_size');

                $table
                    ->unsignedInteger('height')
                    ->nullable()
                    ->after('width');
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'product_images',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'original_name',
                    'mime_type',
                    'file_size',
                    'width',
                    'height',
                ]);
            },
        );
    }
};
