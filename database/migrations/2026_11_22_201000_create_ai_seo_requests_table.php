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
            'ai_seo_requests',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Provider-agnostic subject reference.
                 *
                 * H.2 will resolve/validate the supported
                 * entity and build the immutable snapshot.
                 */
                $table->string(
                    'entity_type',
                    40,
                );

                $table->unsignedBigInteger(
                    'entity_id',
                );

                $table->string(
                    'action',
                    60,
                );

                $table
                    ->string(
                        'status',
                        30,
                    )
                    ->default(
                        'pending',
                    );

                /*
                 * Audit identity only.
                 *
                 * Deliberately not a foreign key: historical
                 * AI request records must survive later admin
                 * account deletion.
                 */
                $table
                    ->unsignedBigInteger(
                        'requested_by_admin_id',
                    )
                    ->nullable();

                /*
                 * Immutable input used for this AI request.
                 */
                $table->json(
                    'input_snapshot',
                );

                $table->char(
                    'input_hash',
                    64,
                );

                /*
                 * Filled when execution starts in H.3.
                 */
                $table
                    ->string(
                        'provider',
                        80,
                    )
                    ->nullable();

                $table
                    ->string(
                        'model_name',
                        160,
                    )
                    ->nullable();

                $table
                    ->string(
                        'prompt_version',
                        80,
                    )
                    ->nullable();

                /*
                 * Raw structured provider response retained
                 * for audit/debugging. Never rendered directly
                 * into storefront HTML.
                 */
                $table
                    ->json(
                        'raw_response',
                    )
                    ->nullable();

                $table
                    ->string(
                        'error_code',
                        120,
                    )
                    ->nullable();

                $table
                    ->text(
                        'error_message',
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'started_at',
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'completed_at',
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'cancelled_at',
                    )
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'entity_type',
                    'entity_id',
                ]);

                $table->index([
                    'status',
                    'created_at',
                ]);

                $table->index(
                    'requested_by_admin_id',
                );

                $table->index(
                    'input_hash',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_requests',
        );
    }
};
