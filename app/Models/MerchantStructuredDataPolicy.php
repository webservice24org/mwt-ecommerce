<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Merchant\Enums\MerchantRefundType;
use App\Domain\Seo\Merchant\Enums\MerchantReturnFees;
use App\Domain\Seo\Merchant\Enums\MerchantReturnMethod;
use App\Domain\Seo\Merchant\Enums\MerchantReturnPolicyCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $scope_key
 * @property bool $shipping_markup_enabled
 * @property list<string>|null $shipping_country_codes
 * @property bool $return_policy_enabled
 * @property list<string>|null $return_applicable_country_codes
 * @property MerchantReturnPolicyCategory|null $return_policy_category
 * @property int|null $return_days
 * @property string|null $return_policy_url
 * @property string|null $return_country_code
 * @property MerchantReturnMethod|null $return_method
 * @property MerchantReturnFees|null $return_fees
 * @property int|null $return_shipping_fee_amount
 * @property MerchantRefundType|null $refund_type
 * @property int $version
 * @property int|null $updated_by_admin_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class MerchantStructuredDataPolicy extends Model
{
    protected $fillable = [
        'scope_key',
        'shipping_markup_enabled',
        'shipping_country_codes',
        'return_policy_enabled',
        'return_applicable_country_codes',
        'return_policy_category',
        'return_days',
        'return_policy_url',
        'return_country_code',
        'return_method',
        'return_fees',
        'return_shipping_fee_amount',
        'refund_type',
        'version',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'shipping_markup_enabled' => 'boolean',

            'shipping_country_codes' => 'array',

            'return_policy_enabled' => 'boolean',

            'return_applicable_country_codes' => 'array',

            'return_policy_category' => MerchantReturnPolicyCategory::class,

            'return_days' => 'integer',

            'return_method' => MerchantReturnMethod::class,

            'return_fees' => MerchantReturnFees::class,

            'return_shipping_fee_amount' => 'integer',

            'refund_type' => MerchantRefundType::class,

            'version' => 'integer',

            'updated_by_admin_id' => 'integer',
        ];
    }

    /**
     * @return HasMany<MerchantStructuredDataPolicyEvent, $this>
     */
    public function events(): HasMany
    {
        return $this
            ->hasMany(
                MerchantStructuredDataPolicyEvent::class,
                'merchant_structured_data_policy_id',
            )
            ->orderByDesc(
                'version',
            );
    }
}
