<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Orders\Enums\FulfilmentStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $delivery_address_id
 * @property string|null $idempotency_key
 * @property string $order_number
 * @property OrderStatus $status
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $payment_status
 * @property FulfilmentStatus $fulfilment_status
 * @property string $currency_code
 * @property int $subtotal
 * @property int $discount_total
 * @property int $delivery_total
 * @property int $tax_total
 * @property int $grand_total
 * @property Carbon|null $payment_due_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $placed_at
 */
final class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'idempotency_key',
        'delivery_address_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'payment_due_at',
        'paid_at',
        'fulfilment_status',
        'currency_code',
        'subtotal',
        'discount_total',
        'delivery_total',
        'tax_total',
        'grand_total',
        'delivery_recipient_name',
        'delivery_phone',
        'delivery_address_line_1',
        'delivery_address_line_2',
        'delivery_city',
        'delivery_state_or_region',
        'delivery_postal_code',
        'delivery_country_code',
        'placed_at',
        'shipping_method_id',
        'shipping_rate_id',
        'shipping_method_code',
        'shipping_method_name',
        'shipping_rate_code',
        'shipping_rate_name',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
        );
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(
            Address::class,
            'delivery_address_id',
        );
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
        );
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this
            ->hasMany(
                OrderStatusHistory::class,
            )
            ->orderBy(
                'changed_at',
            )
            ->orderBy(
                'id',
            );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,

            'payment_method' => PaymentMethod::class,

            'payment_status' => PaymentStatus::class,
            'payment_due_at' => 'datetime',

            'paid_at' => 'datetime',

            'fulfilment_status' => FulfilmentStatus::class,

            'subtotal' => 'integer',

            'discount_total' => 'integer',

            'delivery_total' => 'integer',

            'tax_total' => 'integer',

            'grand_total' => 'integer',

            'placed_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(
            Invoice::class,
        );
    }

    /**
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(
            Shipment::class,
        );
    }

    /**
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(
            ShippingMethod::class,
        );
    }

    /**
     * @return BelongsTo<ShippingRate, $this>
     */
    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(
            ShippingRate::class,
        );
    }

    /**
     * @return HasMany<PromoCodeUsage, $this>
     */
    public function promoCodeUsages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }
}
