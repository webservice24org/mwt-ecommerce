<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_ai_governance_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('governance_run_id');
            $table->unsignedSmallInteger('attempt_number');
            $table->string('outcome', 16);
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedBigInteger('cost_amount_minor')->nullable();
            $table->char('cost_currency', 3)->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->dateTime('recorded_at');

            $table->unique(
                ['governance_run_id', 'attempt_number'],
                'saiga_run_attempt_uidx',
            );
            $table->index(['outcome', 'recorded_at'], 'saiga_outcome_time_idx');
            $table->foreign('governance_run_id', 'saiga_run_fk')
                ->references('id')
                ->on('seo_ai_governance_runs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_ai_governance_attempts');
    }
};
