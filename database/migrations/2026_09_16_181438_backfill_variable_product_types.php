<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('product_variants')
                    ->whereColumn(
                        'product_variants.product_id',
                        'products.id',
                    );
            })
            ->update([
                'type' => 'variable',
            ]);
    }

    public function down(): void
    {
        /*
         * Intentionally irreversible.
         *
         * We cannot safely infer which products were originally
         * simple after correcting products that already had variants.
         */
    }
};
