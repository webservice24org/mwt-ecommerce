<?php

declare(strict_types=1);

use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shipping_provider_accounts',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * One merchant account per courier provider
                 * for the current single-store architecture.
                 *
                 * Pathao may still expose multiple pickup
                 * stores inside this one merchant account.
                 */
                $table
                    ->string(
                        'provider_code',
                        50,
                    )
                    ->unique();

                $table
                    ->string(
                        'name',
                        120,
                    );

                $table
                    ->boolean(
                        'is_active',
                    )
                    ->default(
                        false,
                    );

                $table
                    ->boolean(
                        'is_default',
                    )
                    ->default(
                        false,
                    );

                $table
                    ->boolean(
                        'auto_submit',
                    )
                    ->default(
                        false,
                    );

                $table
                    ->string(
                        'connection_status',
                        32,
                    )
                    ->default(
                        ShippingProviderConnectionStatus::Disconnected
                            ->value,
                    );

                /*
                 * IMPORTANT:
                 *
                 * This is TEXT instead of JSON because the
                 * encrypted:array cast stores encrypted
                 * ciphertext, not JSON.
                 */
                $table
                    ->text(
                        'credentials',
                    )
                    ->nullable();

                /*
                 * Non-secret provider preferences:
                 *
                 * Pathao store ID
                 * delivery type
                 * item type
                 * pickup preferences
                 * future Steadfast settings
                 */
                $table
                    ->json(
                        'settings',
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'last_tested_at',
                    )
                    ->nullable();

                $table
                    ->timestamp(
                        'last_connected_at',
                    )
                    ->nullable();

                $table
                    ->text(
                        'last_error',
                    )
                    ->nullable();

                $table
                    ->foreignId(
                        'created_by_admin_id',
                    )
                    ->nullable()
                    ->constrained(
                        'admins',
                    )
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'updated_by_admin_id',
                    )
                    ->nullable()
                    ->constrained(
                        'admins',
                    )
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'is_active',
                        'is_default',
                    ],
                    'shipping_provider_accounts_operation_idx',
                );

                $table->index(
                    'connection_status',
                    'shipping_provider_accounts_connection_idx',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shipping_provider_accounts',
        );
    }
};
