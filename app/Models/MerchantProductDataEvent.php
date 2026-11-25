<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $merchant_product_data_id
 * @property int $version
 * @property array<string, mixed>|null $before_data
 * @property array<string, mixed> $after_data
 * @property int|null $changed_by_admin_id
 * @property Carbon $created_at
 */
final class MerchantProductDataEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'merchant_product_data_id',
        'version',
        'before_data',
        'after_data',
        'changed_by_admin_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'merchant_product_data_id' => 'integer',

            'version' => 'integer',

            'before_data' => 'array',

            'after_data' => 'array',

            'changed_by_admin_id' => 'integer',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MerchantProductData, $this>
     */
    public function data(): BelongsTo
    {
        return $this->belongsTo(
            MerchantProductData::class,
            'merchant_product_data_id',
        );
    }
}
