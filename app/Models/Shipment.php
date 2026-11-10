<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Enums\ShippingProviderCode;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $shipping_method_id
 * @property ShippingProviderCode|null $provider_code
 * @property int|null $shipping_provider_account_id
 * @property ShipmentStatus $status
 * @property string|null $carrier
 * @property string|null $service
 * @property string|null $tracking_number
 * @property string|null $tracking_url
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $returned_at
 * @property array<string, mixed>|null $metadata
 */
final class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',

        /*
         * Commercial service selected at checkout.
         * This does NOT determine the courier anymore.
         */
        'shipping_method_id',

        /*
         * Physical fulfilment provider.
         */
        'provider_code',
        'shipping_provider_account_id',

        'status',
        'carrier',
        'service',
        'tracking_number',
        'tracking_url',
        'shipped_at',
        'delivered_at',
        'returned_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'provider_code' => ShippingProviderCode::class,

            'status' => ShipmentStatus::class,

            'shipped_at' => 'datetime',

            'delivered_at' => 'datetime',

            'returned_at' => 'datetime',

            'metadata' => 'array',
        ];
    }

    /**
     * New shipments have provider_code explicitly.
     *
     * Legacy Phase 8 shipments fall back to the old
     * ShippingMethod.driver relationship so existing
     * provider submissions remain readable/recoverable.
     */
    public function effectiveProviderCode(): ?string
    {
        if (
            $this->provider_code instanceof ShippingProviderCode
        ) {
            return $this
                ->provider_code
                ->value;
        }

        $this->loadMissing(
            'shippingMethod',
        );

        $driver =
            $this
                ->shippingMethod
                ?->driver;

        if (
            ! is_string($driver) ||
            trim($driver) === ''
        ) {
            return null;
        }

        return strtolower(
            trim(
                $driver,
            ),
        );
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
        );
    }

    /**
     * Checkout delivery service.
     *
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(
            ShippingMethod::class,
        );
    }

    /**
     * Physical courier merchant account.
     *
     * @return BelongsTo<ShippingProviderAccount, $this>
     */
    public function shippingProviderAccount(): BelongsTo
    {
        return $this->belongsTo(
            ShippingProviderAccount::class,
            'shipping_provider_account_id',
        );
    }

    /**
     * Provider submission state for this shipment.
     *
     * A shipment owns at most one external provider
     * submission row. Retries reuse that same row and
     * idempotency key.
     *
     * @return HasOne<ShipmentProviderSubmission, $this>
     */
    public function providerSubmission(): HasOne
    {
        return $this->hasOne(
            ShipmentProviderSubmission::class,
        );
    }

    /**
     * @return HasMany<ShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            ShipmentItem::class,
        );
    }
}
