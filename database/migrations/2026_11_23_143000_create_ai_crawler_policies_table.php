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
            'ai_crawler_policies',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string(
                        'scope_key',
                        20,
                    )
                    ->unique(
                        'ai_crawler_policy_scope_uq',
                    );

                $table
                    ->boolean(
                        'allow_oai_search_bot',
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'allow_gpt_bot',
                    )
                    ->default(true);

                $table
                    ->boolean(
                        'allow_google_extended',
                    )
                    ->default(true);

                $table
                    ->unsignedInteger(
                        'version',
                    )
                    ->default(1);

                /*
                 * Audit identity only. No FK so the policy history
                 * survives later administrator deletion.
                 */
                $table
                    ->unsignedBigInteger(
                        'updated_by_admin_id',
                    )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->dateTime(
                    'updated_at',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_crawler_policies',
        );
    }
};
