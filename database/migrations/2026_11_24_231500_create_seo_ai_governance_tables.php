<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_ai_governance_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('saigr_public_uidx');
            $table->string('purpose', 40);
            $table->string('subject_type', 40)->nullable();
            $table->string('subject_key', 190)->nullable();
            $table->string('provider', 80);
            $table->string('model_name', 120);
            $table->string('prompt_version', 80);
            $table->char('input_snapshot_hash', 64);
            $table->json('input_field_names')->nullable();
            $table->string('status', 24);
            $table->string('approval_status', 24);
            $table->json('generated_output')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->unsignedBigInteger('requested_by_admin_id')->nullable();
            $table->string('requested_by_name', 120)->nullable();
            $table->string('requested_by_email', 190)->nullable();
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable();
            $table->string('reviewed_by_name', 120)->nullable();
            $table->string('reviewed_by_email', 190)->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('failure_code', 80)->nullable();
            $table->string('failure_message', 255)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedBigInteger('cost_amount_minor')->nullable();
            $table->char('cost_currency', 3)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'saigr_status_created_idx');
            $table->index(['approval_status', 'created_at'], 'saigr_approval_created_idx');
            $table->index(['purpose', 'created_at'], 'saigr_purpose_created_idx');
            $table->index(['subject_type', 'subject_key'], 'saigr_subject_idx');
            $table->index('input_snapshot_hash', 'saigr_input_hash_idx');

            $table->foreign('requested_by_admin_id', 'saigr_req_admin_fk')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
            $table->foreign('reviewed_by_admin_id', 'saigr_rev_admin_fk')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });

        Schema::create('seo_ai_governance_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('governance_run_id');
            $table->string('event_type', 24);
            $table->unsignedBigInteger('actor_admin_id')->nullable();
            $table->string('actor_name', 120)->nullable();
            $table->string('actor_email', 190)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');

            $table->index(['governance_run_id', 'id'], 'saige_run_id_idx');
            $table->foreign('governance_run_id', 'saige_run_fk')
                ->references('id')
                ->on('seo_ai_governance_runs')
                ->cascadeOnDelete();
            $table->foreign('actor_admin_id', 'saige_actor_admin_fk')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_ai_governance_events');
        Schema::dropIfExists('seo_ai_governance_runs');
    }
};
