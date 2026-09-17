<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table
                ->string('type', 20)
                ->default('simple')
                ->index();

            $table
                ->string('sku', 100)
                ->nullable()
                ->unique();

            $table
                ->unsignedBigInteger('price')
                ->nullable();

            $table
                ->unsignedBigInteger('compare_at_price')
                ->nullable();

            $table
                ->unsignedBigInteger('cost_price')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique([
                'sku',
            ]);

            $table->dropIndex([
                'type',
            ]);

            $table->dropColumn([
                'type',
                'sku',
                'price',
                'compare_at_price',
                'cost_price',
            ]);
        });
    }
};
