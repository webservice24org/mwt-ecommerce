<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_performance_connections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique('spc_public_uidx');
            $table->string('provider', 40);
            $table->string('status', 24)->default('configured');
            $table->string('display_name', 120);
            $table->string('property_identifier', 255);
            $table->text('property_url')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->json('capabilities');
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->string('created_by_name', 120)->nullable();
            $table->string('created_by_email', 255)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('last_error_code', 80)->nullable();
            $table->string('last_error_message', 500)->nullable();
            $table->timestamps();

            $table->unique(
                ['provider', 'property_identifier'],
                'spc_provider_property_uidx',
            );
            $table->index(
                ['provider', 'status'],
                'spc_provider_status_idx',
            );
            $table->foreign(
                'created_by_admin_id',
                'spc_created_admin_fk',
            )
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_performance_connections');
    }
};
