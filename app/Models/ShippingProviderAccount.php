<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shipping\Enums\ShippingProviderCode;
use App\Domain\Shipping\Enums\ShippingProviderConnectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ShippingProviderCode $provider_code
 * @property string $name
 * @property bool $is_active
 * @property bool $is_default
 * @property bool $auto_submit
 * @property ShippingProviderConnectionStatus $connection_status
 * @property array<string, mixed>|null $credentials
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $last_tested_at
 * @property Carbon|null $last_connected_at
 * @property string|null $last_error
 * @property int|null $created_by_admin_id
 * @property int|null $updated_by_admin_id
 */
final class ShippingProviderAccount extends Model
{
    /**
     * Credentials must never leak through accidental
     * model serialization / Inertia props / JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'credentials',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'provider_code',
        'name',
        'is_active',
        'is_default',
        'auto_submit',
        'connection_status',
        'credentials',
        'settings',
        'last_tested_at',
        'last_connected_at',
        'last_error',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider_code' => ShippingProviderCode::class,

            'is_active' => 'boolean',

            'is_default' => 'boolean',

            'auto_submit' => 'boolean',

            'connection_status' => ShippingProviderConnectionStatus::class,

            /*
             * Laravel encrypts the complete credential
             * payload before writing it to the database.
             */
            'credentials' => 'encrypted:array',

            'settings' => 'array',

            'last_tested_at' => 'datetime',

            'last_connected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'created_by_admin_id',
        );
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'updated_by_admin_id',
        );
    }

    public function isConnected(): bool
    {
        return $this
            ->connection_status ===
            ShippingProviderConnectionStatus::Connected;
    }

    public function isOperational(): bool
    {
        return $this->is_active
            && $this->isConnected();
    }
}
