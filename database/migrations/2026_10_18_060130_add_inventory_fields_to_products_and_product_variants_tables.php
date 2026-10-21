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
            'products',
            function (Blueprint $table): void {
                $table
                    ->boolean('track_inventory')
                    ->default(false)
                    ->after('cost_price');

                $table
                    ->unsignedInteger('stock_quantity')
                    ->default(0)
                    ->after('track_inventory');
            },
        );

        Schema::table(
            'product_variants',
            function (Blueprint $table): void {
                $table
                    ->unsignedInteger('stock_quantity')
                    ->default(0)
                    ->after('cost_price');
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'product_variants',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'stock_quantity',
                );
            },
        );

        Schema::table(
            'products',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'track_inventory',
                    'stock_quantity',
                ]);
            },
        );
    }
};
