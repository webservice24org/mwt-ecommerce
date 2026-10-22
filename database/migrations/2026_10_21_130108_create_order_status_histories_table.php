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
            'order_status_histories',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'order_id',
                    )
                    ->constrained(
                        'orders',
                    )
                    ->cascadeOnDelete();

                /*
                 * Which state family changed:
                 *
                 * order
                 * payment
                 * fulfilment
                 */
                $table->string(
                    'type',
                    32,
                );

                $table->string(
                    'from_status',
                    64,
                )->nullable();

                $table->string(
                    'to_status',
                    64,
                );

                /*
                 * Generic actor reference.
                 *
                 * This allows later transitions to be
                 * performed by:
                 *
                 * - customer User
                 * - Admin
                 * - system/background process
                 *
                 * without tying this audit table to one
                 * authentication model.
                 */
                $table->nullableMorphs(
                    'actor',
                );

                $table->text(
                    'note',
                )->nullable();

                $table->json(
                    'metadata',
                )->nullable();

                $table->timestamp(
                    'changed_at',
                );

                $table->timestamps();

                $table->index(
                    [
                        'order_id',
                        'type',
                        'changed_at',
                    ],
                    'order_status_histories_order_type_changed_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'order_status_histories',
        );
    }
};
