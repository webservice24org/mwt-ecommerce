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
            'ai_crawler_policy_events',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->unsignedBigInteger(
                        'ai_crawler_policy_id',
                    );

                $table
                    ->foreign(
                        'ai_crawler_policy_id',
                        'ai_crawler_event_policy_fk',
                    )
                    ->references('id')
                    ->on(
                        'ai_crawler_policies',
                    )
                    ->cascadeOnDelete();

                $table
                    ->unsignedInteger(
                        'version',
                    );

                $table->json(
                    'before_policy',
                )
                    ->nullable();

                $table->json(
                    'after_policy',
                );

                $table
                    ->unsignedBigInteger(
                        'changed_by_admin_id',
                    )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->unique(
                    [
                        'ai_crawler_policy_id',
                        'version',
                    ],
                    'ai_crawler_event_policy_version_uq',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'ai_crawler_policy_events',
        );
    }
};
