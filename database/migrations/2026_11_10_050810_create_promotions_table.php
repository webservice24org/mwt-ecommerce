<?php

declare(strict_types=1);

use App\Domain\Promotion\Enums\DiscountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table
                ->string('discount_type', 32)
                ->default(DiscountType::Fixed->value);

            /*
             * fixed:
             *     integer currency value
             *
             * percentage:
             *     basis points
             *     1000 = 10%
             *     10000 = 100%
             */
            $table->unsignedBigInteger('discount_value');

            /*
             * Applicable mainly to percentage promotions.
             * Null = no maximum discount cap.
             */
            $table
                ->unsignedBigInteger('maximum_discount')
                ->nullable();

            /*
             * Promotion becomes eligible only when the
             * qualifying subtotal reaches this amount.
             */
            $table
                ->unsignedBigInteger('minimum_subtotal')
                ->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            /*
             * Reserved for future promotion composition rules.
             * Phase 9 initially treats non-stackable as authoritative.
             */
            $table->boolean('is_stackable')->default(false);

            $table->timestamps();

            $table->index([
                'is_active',
                'starts_at',
                'ends_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
