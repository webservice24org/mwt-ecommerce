<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Merchant\Enums\MerchantIdentifierState;
use App\Domain\Seo\Merchant\Enums\MerchantProductCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $item_key
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property MerchantProductCondition $condition
 * @property MerchantIdentifierState $identifier_state
 * @property string|null $gtin
 * @property string|null $mpn
 * @property string|null $google_product_category
 * @property int $version
 * @property int|null $updated_by_admin_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class MerchantProductData extends Model
{
    protected $fillable = [
        'item_key',
        'product_id',
        'product_variant_id',
        'condition',
        'identifier_state',
        'gtin',
        'mpn',
        'google_product_category',
        'version',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',

            'product_variant_id' => 'integer',

            'condition' => MerchantProductCondition::class,

            'identifier_state' => MerchantIdentifierState::class,

            'version' => 'integer',

            'updated_by_admin_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
        );
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id',
        );
    }

    /**
     * @return HasMany<MerchantProductDataEvent, $this>
     */
    public function events(): HasMany
    {
        return $this
            ->hasMany(
                MerchantProductDataEvent::class,
            )
            ->orderByDesc(
                'version',
            );
    }
}
