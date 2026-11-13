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
            'product_reviews',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'moderated_by',
                ]);

                $table
                    ->foreign('moderated_by')
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'product_reviews',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'moderated_by',
                ]);

                $table
                    ->foreign('moderated_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            },
        );
    }
};
