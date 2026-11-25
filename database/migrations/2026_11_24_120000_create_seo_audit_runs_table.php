<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_audit_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('sar_public_uidx');
            $table->foreignId('requested_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('requested_by_name');
            $table->string('requested_by_email');
            $table->string('status', 20);
            $table->string('rules_version', 64);
            $table->unsignedInteger('urls_scanned')->default(0);
            $table->unsignedInteger('findings_total')->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('info_count')->default(0);
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'sar_status_created_idx');
            $table->index(['requested_by_admin_id', 'created_at'], 'sar_admin_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_audit_runs');
    }
};
