<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $merchant_structured_data_policy_id
 * @property int $version
 * @property array<string, mixed>|null $before_policy
 * @property array<string, mixed> $after_policy
 * @property int|null $changed_by_admin_id
 * @property Carbon $created_at
 */
final class MerchantStructuredDataPolicyEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'merchant_structured_data_policy_id',
        'version',
        'before_policy',
        'after_policy',
        'changed_by_admin_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'merchant_structured_data_policy_id' => 'integer',

            'version' => 'integer',

            'before_policy' => 'array',

            'after_policy' => 'array',

            'changed_by_admin_id' => 'integer',

            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MerchantStructuredDataPolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(
            MerchantStructuredDataPolicy::class,
            'merchant_structured_data_policy_id',
        );
    }
}
