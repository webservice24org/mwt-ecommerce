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
            'seo_audit_image_resources',
            function (Blueprint $table): void {
                $table->id();
                $table
                    ->foreignId('seo_audit_run_id')
                    ->constrained('seo_audit_runs')
                    ->cascadeOnDelete();
                $table->char('image_url_hash', 64);
                $table->text('image_url');
                $table->string('state', 32);
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->string('content_type', 150)->nullable();
                $table->string('failure_code', 100)->nullable();
                $table->text('failure_message')->nullable();
                $table->timestamp('checked_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['seo_audit_run_id', 'image_url_hash'],
                    'sair_run_image_uidx',
                );

                $table->index(
                    ['seo_audit_run_id', 'state'],
                    'sair_run_state_idx',
                );

                $table->index(
                    ['seo_audit_run_id', 'http_status'],
                    'sair_run_status_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'seo_audit_image_resources',
        );
    }
};
