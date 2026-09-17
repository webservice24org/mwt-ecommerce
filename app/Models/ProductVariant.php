<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string|null $name
 * @property int|null $price
 * @property int|null $compare_at_price
 * @property int|null $cost_price
 * @property string|null $barcode
 * @property int $position
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $weight
 */
final class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'compare_at_price',
        'cost_price',
        'barcode',
        'position',
        'is_active',
        'is_default',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'cost_price' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'weight' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<AttributeValue, $this>
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_values',
            'product_variant_id',
            'attribute_value_id',
        )->withTimestamps();
    }
}
