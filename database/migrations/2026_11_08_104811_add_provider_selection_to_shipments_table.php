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
            'shipments',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'provider_code',
                        50,
                    )
                    ->nullable()
                    ->after(
                        'shipping_method_id',
                    );

                $table
                    ->foreignId(
                        'shipping_provider_account_id',
                    )
                    ->nullable()
                    ->after(
                        'provider_code',
                    )
                    ->constrained(
                        'shipping_provider_accounts',
                    )
                    ->nullOnDelete();

                $table->index(
                    'provider_code',
                    'shipments_provider_code_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'shipments',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'shipping_provider_account_id',
                ]);

                $table->dropIndex(
                    'shipments_provider_code_idx',
                );

                $table->dropColumn([
                    'provider_code',
                    'shipping_provider_account_id',
                ]);
            },
        );
    }
};
