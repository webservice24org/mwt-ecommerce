<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id();

            /*
             * Nullable allows a standalone promo code.
             *
             * Later a promo code may belong to a broader
             * promotion/campaign.
             */
            $table
                ->foreignId('promotion_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code', 100)->unique();

            $table->string('name');
            $table->text('description')->nullable();

            /*
             * Optional overrides.
             *
             * If promotion_id exists and these are null,
             * the parent promotion values can be used.
             *
             * Standalone promo codes will require values
             * during application-level validation.
             */
            $table
                ->string('discount_type', 32)
                ->nullable();

            $table
                ->unsignedBigInteger('discount_value')
                ->nullable();

            $table
                ->unsignedBigInteger('maximum_discount')
                ->nullable();

            $table
                ->unsignedBigInteger('minimum_subtotal')
                ->nullable();

            /*
             * Null means unlimited.
             */
            $table
                ->unsignedInteger('usage_limit')
                ->nullable();

            /*
             * Null means no customer-specific cap.
             */
            $table
                ->unsignedInteger('usage_limit_per_customer')
                ->nullable();

            /*
             * Denormalized count used for efficient,
             * lock-protected redemption checks.
             *
             * promo_code_usages remains the audit truth.
             */
            $table
                ->unsignedInteger('redeemed_count')
                ->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table
                ->boolean('is_stackable')
                ->default(false);

            $table->timestamps();

            $table->index([
                'is_active',
                'starts_at',
                'ends_at',
            ]);

            $table->index([
                'promotion_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
