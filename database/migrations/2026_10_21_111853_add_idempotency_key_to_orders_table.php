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
            'orders',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'idempotency_key',
                        64,
                    )
                    ->nullable()
                    ->after(
                        'user_id',
                    );

                $table->unique(
                    [
                        'user_id',
                        'idempotency_key',
                    ],
                    'orders_user_id_idempotency_key_unique',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'orders_user_id_idempotency_key_unique',
                );

                $table->dropColumn(
                    'idempotency_key',
                );
            },
        );
    }
};
