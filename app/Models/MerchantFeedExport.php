<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Seo\Merchant\Enums\MerchantFeedExportFormat;
use App\Domain\Seo\Merchant\Enums\MerchantFeedExportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $requested_by_admin_id
 * @property string $requested_by_name
 * @property string $requested_by_email
 * @property MerchantFeedExportStatus $status
 * @property MerchantFeedExportFormat $format
 * @property string $schema_version
 * @property string $readiness_policy_version
 * @property int $merchant_policy_version
 * @property string $snapshot_hash
 * @property array<string, mixed> $snapshot
 * @property int $products_considered
 * @property int $item_candidates
 * @property int $exportable_count
 * @property int $excluded_count
 * @property int $out_of_stock_count
 * @property int $blocker_count
 * @property int $warning_count
 * @property int $info_count
 * @property bool $truncated
 * @property int $max_items
 * @property int $attempt_count
 * @property string|null $file_disk
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $file_mime_type
 * @property int|null $file_size
 * @property string|null $file_sha256
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property Carbon $queued_at
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class MerchantFeedExport extends Model
{
    protected $fillable = [
        'public_id',
        'requested_by_admin_id',
        'requested_by_name',
        'requested_by_email',
        'status',
        'format',
        'schema_version',
        'readiness_policy_version',
        'merchant_policy_version',
        'snapshot_hash',
        'snapshot',
        'products_considered',
        'item_candidates',
        'exportable_count',
        'excluded_count',
        'out_of_stock_count',
        'blocker_count',
        'warning_count',
        'info_count',
        'truncated',
        'max_items',
        'attempt_count',
        'file_disk',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
        'file_sha256',
        'failure_code',
        'failure_message',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_by_admin_id' => 'integer',

            'status' => MerchantFeedExportStatus::class,

            'format' => MerchantFeedExportFormat::class,

            'merchant_policy_version' => 'integer',

            'snapshot' => 'array',

            'products_considered' => 'integer',

            'item_candidates' => 'integer',

            'exportable_count' => 'integer',

            'excluded_count' => 'integer',

            'out_of_stock_count' => 'integer',

            'blocker_count' => 'integer',

            'warning_count' => 'integer',

            'info_count' => 'integer',

            'truncated' => 'boolean',

            'max_items' => 'integer',

            'attempt_count' => 'integer',

            'file_size' => 'integer',

            'queued_at' => 'datetime',

            'started_at' => 'datetime',

            'completed_at' => 'datetime',

            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'requested_by_admin_id',
        );
    }

    /**
     * @return HasMany<MerchantFeedExportEvent, $this>
     */
    public function events(): HasMany
    {
        return $this
            ->hasMany(
                MerchantFeedExportEvent::class,
            )
            ->orderBy('id');
    }
}
