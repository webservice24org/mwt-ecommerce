<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('trashed_at')->nullable();
            $table->index(['trashed_at', 'status'], 'orders_trash_status_index');
        });

        // Deliberately independent of orders/admins: the audit survives deletion.
        Schema::create('order_trash_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('order_number', 50);
            $table->unsignedBigInteger('admin_id');
            $table->string('action', 24);
            $table->char('submission_fingerprint', 64)->nullable()->unique();
            $table->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_trash_events');
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_trash_status_index');
            $table->dropColumn('trashed_at');
        });
    }
};
