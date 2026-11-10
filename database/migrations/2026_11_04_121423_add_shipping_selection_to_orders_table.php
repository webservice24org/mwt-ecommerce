<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->after('delivery_address_id')
                ->constrained('shipping_methods')
                ->nullOnDelete();

            $table->foreignId('shipping_rate_id')
                ->nullable()
                ->after('shipping_method_id')
                ->constrained('shipping_rates')
                ->nullOnDelete();

            $table->string('shipping_method_code', 80)
                ->nullable()
                ->after('shipping_rate_id');

            $table->string('shipping_method_name', 120)
                ->nullable()
                ->after('shipping_method_code');

            $table->string('shipping_rate_code', 80)
                ->nullable()
                ->after('shipping_method_name');

            $table->string('shipping_rate_name', 120)
                ->nullable()
                ->after('shipping_rate_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign([
                'shipping_rate_id',
            ]);

            $table->dropForeign([
                'shipping_method_id',
            ]);

            $table->dropColumn([
                'shipping_method_id',
                'shipping_rate_id',
                'shipping_method_code',
                'shipping_method_name',
                'shipping_rate_code',
                'shipping_rate_name',
            ]);
        });
    }
};
