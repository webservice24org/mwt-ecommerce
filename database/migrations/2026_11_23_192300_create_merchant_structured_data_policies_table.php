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
            'merchant_structured_data_policies',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->string(
                        'scope_key',
                        50,
                    )
                    ->default('global');

                $table
                    ->boolean(
                        'shipping_markup_enabled',
                    )
                    ->default(false);

                $table
                    ->json(
                        'shipping_country_codes',
                    )
                    ->nullable();

                $table
                    ->boolean(
                        'return_policy_enabled',
                    )
                    ->default(false);

                $table
                    ->json(
                        'return_applicable_country_codes',
                    )
                    ->nullable();

                $table
                    ->string(
                        'return_policy_category',
                        50,
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'return_days',
                    )
                    ->nullable();

                $table
                    ->string(
                        'return_policy_url',
                        2048,
                    )
                    ->nullable();

                $table
                    ->string(
                        'return_country_code',
                        2,
                    )
                    ->nullable();

                $table
                    ->string(
                        'return_method',
                        50,
                    )
                    ->nullable();

                $table
                    ->string(
                        'return_fees',
                        50,
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'return_shipping_fee_amount',
                    )
                    ->nullable();

                $table
                    ->string(
                        'refund_type',
                        50,
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'version',
                    )
                    ->default(1);

                $table
                    ->unsignedBigInteger(
                        'updated_by_admin_id',
                    )
                    ->nullable();

                $table->dateTime(
                    'created_at',
                );

                $table->dateTime(
                    'updated_at',
                );

                $table->unique(
                    'scope_key',
                    'merchant_sd_policy_scope_uq',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'merchant_structured_data_policies',
        );
    }
};
