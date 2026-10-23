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
            'users',
            function (Blueprint $table): void {
                $table
                    ->timestamp('anonymized_at')
                    ->nullable()
                    ->after('status')
                    ->index();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'anonymized_at',
                );
            },
        );
    }
};
