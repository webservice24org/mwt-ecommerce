<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'seo_audit_internal_link_opportunities';

    private const REVIEW_INDEX = 'sailo_run_reviewed_at_idx';

    private const REVIEWER_FOREIGN = 'sailo_reviewer_admin_fk';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'reviewed_by_admin_id')) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table
                        ->foreignId('reviewed_by_admin_id')
                        ->nullable()
                        ->after('review_state');
                },
            );
        }

        if (! Schema::hasColumn(self::TABLE, 'reviewed_by_name')) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table
                        ->string('reviewed_by_name')
                        ->nullable()
                        ->after('reviewed_by_admin_id');
                },
            );
        }

        if (! Schema::hasColumn(self::TABLE, 'reviewed_by_email')) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table
                        ->string('reviewed_by_email')
                        ->nullable()
                        ->after('reviewed_by_name');
                },
            );
        }

        if (! Schema::hasColumn(self::TABLE, 'reviewed_at')) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table
                        ->timestamp('reviewed_at')
                        ->nullable()
                        ->after('reviewed_by_email');
                },
            );
        }

        if (! Schema::hasColumn(self::TABLE, 'review_note')) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table
                        ->string('review_note', 500)
                        ->nullable()
                        ->after('reviewed_at');
                },
            );
        }

        if (! Schema::hasIndex(self::TABLE, self::REVIEW_INDEX)) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table->index(
                        ['seo_audit_run_id', 'reviewed_at'],
                        self::REVIEW_INDEX,
                    );
                },
            );
        }

        Schema::table(
            self::TABLE,
            function (Blueprint $table): void {
                $table
                    ->foreign(
                        'reviewed_by_admin_id',
                        self::REVIEWER_FOREIGN,
                    )
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            self::TABLE,
            function (Blueprint $table): void {
                $table->dropForeign(self::REVIEWER_FOREIGN);
            },
        );

        if (Schema::hasIndex(self::TABLE, self::REVIEW_INDEX)) {
            Schema::table(
                self::TABLE,
                function (Blueprint $table): void {
                    $table->dropIndex(self::REVIEW_INDEX);
                },
            );
        }

        Schema::table(
            self::TABLE,
            function (Blueprint $table): void {
                $columns = [
                    'reviewed_by_admin_id',
                    'reviewed_by_name',
                    'reviewed_by_email',
                    'reviewed_at',
                    'review_note',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn(self::TABLE, $column)) {
                        $table->dropColumn($column);
                    }
                }
            },
        );
    }
};
