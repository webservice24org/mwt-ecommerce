<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Promotion\Enums\DiscountType;
use App\Domain\Promotion\Support\PromoCodeNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'promotion_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'maximum_discount',
        'minimum_subtotal',
        'usage_limit',
        'usage_limit_per_customer',
        'redeemed_count',
        'starts_at',
        'ends_at',
        'is_active',
        'is_stackable',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,

            'discount_value' => 'integer',
            'maximum_discount' => 'integer',
            'minimum_subtotal' => 'integer',

            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'redeemed_count' => 'integer',

            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',

            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Promotion, $this>
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * @return HasMany<PromoCodeUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] =
            PromoCodeNormalizer::normalize($value);
    }
}
