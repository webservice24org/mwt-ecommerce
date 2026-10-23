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
            'customer_status_histories',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->string(
                    'from_status',
                    30,
                );

                $table->string(
                    'to_status',
                    30,
                );

                $table
                    ->text('reason')
                    ->nullable();

                /*
                 * Admin or another future actor.
                 *
                 * Creates:
                 * actor_type
                 * actor_id
                 */
                $table->nullableMorphs(
                    'actor',
                );

                /*
                 * Snapshot the actor name so the audit
                 * remains understandable even if the
                 * administrator changes their name later.
                 */
                $table
                    ->string(
                        'actor_name',
                        120,
                    )
                    ->nullable();

                $table->timestamp(
                    'changed_at',
                );

                $table->timestamps();

                $table->index([
                    'user_id',
                    'changed_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'customer_status_histories',
        );
    }
};
