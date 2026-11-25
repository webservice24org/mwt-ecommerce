<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportEventType;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $merchant_feed_export_id
 * @property MerchantFeedExportEventType $event_type
 * @property MerchantFeedExportStatus|null $from_status
 * @property MerchantFeedExportStatus $to_status
 * @property int|null $actor_admin_id
 * @property string|null $actor_name
 * @property string|null $actor_email
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 */
final class MerchantFeedExportEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'merchant_feed_export_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_admin_id',
        'actor_name',
        'actor_email',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'merchant_feed_export_id' => 'integer',

            'event_type' => MerchantFeedExportEventType::class,

            'from_status' => MerchantFeedExportStatus::class,

            'to_status' => MerchantFeedExportStatus::class,

            'actor_admin_id' => 'integer',

            'metadata' => 'array',

            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MerchantFeedExport, $this>
     */
    public function export(): BelongsTo
    {
        return $this->belongsTo(
            MerchantFeedExport::class,
        );
    }
}
