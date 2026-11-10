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
                    ->timestamp(
                        'payment_due_at',
                    )
                    ->nullable()
                    ->after(
                        'payment_status',
                    );

                $table
                    ->timestamp(
                        'paid_at',
                    )
                    ->nullable()
                    ->after(
                        'payment_due_at',
                    );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'payment_due_at',
                    'paid_at',
                ]);
            },
        );
    }
};
