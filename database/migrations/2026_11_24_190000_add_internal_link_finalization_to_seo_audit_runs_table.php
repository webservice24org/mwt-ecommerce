<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('seo_audit_runs', 'internal_links_built_at')) {
            return;
        }

        Schema::table('seo_audit_runs', function (Blueprint $table): void {
            $table
                ->timestamp('internal_links_built_at')
                ->nullable()
                ->after('evaluated_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('seo_audit_runs', 'internal_links_built_at')) {
            return;
        }

        Schema::table('seo_audit_runs', function (Blueprint $table): void {
            $table->dropColumn('internal_links_built_at');
        });
    }
};
