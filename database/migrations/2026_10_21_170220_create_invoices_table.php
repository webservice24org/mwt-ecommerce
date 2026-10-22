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
            'invoices',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * One invoice per order.
                 *
                 * The unique constraint is also the final
                 * concurrency authority.
                 */
                $table
                    ->foreignId('order_id')
                    ->constrained('orders')
                    ->restrictOnDelete();

                $table
                    ->string(
                        'invoice_number',
                        64,
                    )
                    ->unique();

                $table
                    ->timestamp(
                        'issued_at',
                    );

                $table->timestamps();

                $table->unique(
                    'order_id',
                    'invoices_order_id_unique',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'invoices',
        );
    }
};
