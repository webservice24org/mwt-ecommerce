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
            'product_variants',
            function (Blueprint $table): void {
                $table
                    ->unsignedBigInteger('price')
                    ->nullable()
                    ->change();
            },
        );
    }

    public function down(): void
    {
        /*
         * Existing NULL variant prices cannot safely be converted back
         * to NOT NULL without first resolving them to product prices.
         */
    }
};
