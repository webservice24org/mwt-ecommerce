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
            'ai_seo_suggestions',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId(
                        'ai_seo_request_id',
                    )
                    ->constrained(
                        'ai_seo_requests',
                    )
                    ->cascadeOnDelete();

                /*
                 * Restricted by AiSeoSuggestionField.
                 *
                 * Canonical, robots, images and structured
                 * data are intentionally not valid fields.
                 */
                $table->string(
                    'field',
                    80,
                );

                $table->text(
                    'value',
                );

                $table
                    ->text(
                        'rationale',
                    )
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'position',
                    )
                    ->default(0);

                $table->timestamps();

                $table->unique([
                    'ai_seo_request_id',
                    'field',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_seo_suggestions',
        );
    }
};
