<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Promotion\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'maximum_discount',
        'minimum_subtotal',
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

            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',

            'is_active' => 'boolean',
            'is_stackable' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PromoCode, $this>
     */
    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class);
    }
}
