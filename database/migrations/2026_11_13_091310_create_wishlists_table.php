<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'wishlists',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('product_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->timestamps();

                /*
                 * A customer may wishlist a product only once.
                 *
                 * This is also the final database-level
                 * concurrency boundary for duplicate requests.
                 */
                $table->unique(
                    [
                        'user_id',
                        'product_id',
                    ],
                    'wishlists_user_product_unique',
                );

                /*
                 * Supports displaying a customer's most
                 * recently wishlisted products efficiently.
                 */
                $table->index(
                    [
                        'user_id',
                        'created_at',
                    ],
                    'wishlists_user_created_index',
                );

                /*
                 * Useful for product-level reverse lookups
                 * and FK/cascade operations.
                 */
                $table->index(
                    'product_id',
                    'wishlists_product_index',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'wishlists',
        );
    }
};
