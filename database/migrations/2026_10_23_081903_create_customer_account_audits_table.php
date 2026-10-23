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
            'customer_account_audits',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Deliberately no FK.
                 *
                 * A hard-deleted customer no longer exists,
                 * but the administrative audit must remain.
                 */
                $table
                    ->unsignedBigInteger(
                        'customer_id',
                    )
                    ->index();

                $table->string(
                    'action',
                    40,
                );

                $table->nullableMorphs(
                    'actor',
                );

                $table
                    ->string(
                        'actor_name',
                        120,
                    )
                    ->nullable();

                $table->text(
                    'reason',
                );

                $table->json(
                    'metadata',
                )->nullable();

                $table->timestamp(
                    'occurred_at',
                );

                $table->timestamps();

                $table->index([
                    'customer_id',
                    'occurred_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'customer_account_audits',
        );
    }
};
