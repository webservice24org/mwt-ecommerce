<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_ai_legacy_request_governance_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('ai_seo_request_id');
            $table->unsignedBigInteger('governance_run_id');
            $table->timestamps();

            $table->unique('ai_seo_request_id', 'saiglr_request_uidx');
            $table->unique('governance_run_id', 'saiglr_run_uidx');

            $table->foreign('ai_seo_request_id', 'saiglr_request_fk')
                ->references('id')
                ->on('ai_seo_requests')
                ->cascadeOnDelete();

            $table->foreign('governance_run_id', 'saiglr_run_fk')
                ->references('id')
                ->on('seo_ai_governance_runs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_ai_legacy_request_governance_links');
    }
};
