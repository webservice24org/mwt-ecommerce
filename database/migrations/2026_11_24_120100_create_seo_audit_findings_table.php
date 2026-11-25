<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_audit_findings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seo_audit_run_id')->constrained('seo_audit_runs')->cascadeOnDelete();
            $table->char('fingerprint', 64);
            $table->string('rule_key', 80);
            $table->string('severity', 20);
            $table->string('subject_type', 32);
            $table->string('subject_key')->nullable();
            $table->text('url')->nullable();
            $table->text('source_url')->nullable();
            $table->string('message', 500);
            $table->json('evidence');
            $table->text('remediation')->nullable();
            $table->timestamps();
            $table->unique(['seo_audit_run_id', 'fingerprint'], 'saf_run_fingerprint_uidx');
            $table->index(['seo_audit_run_id', 'severity'], 'saf_run_severity_idx');
            $table->index(['seo_audit_run_id', 'rule_key'], 'saf_run_rule_idx');
            $table->index(['rule_key', 'subject_type'], 'saf_rule_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audit_findings');
    }
};
