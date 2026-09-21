<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\PageBuilder\Enums\SectionType;
use Database\Factories\PageSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $page_id
 * @property SectionType $type
 * @property string $template
 * @property array<string, mixed> $config
 * @property int $position
 * @property bool $is_enabled
 */
final class PageSection extends Model
{
    /** @use HasFactory<PageSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'page_id',
        'type',
        'template',
        'config',
        'position',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'config' => 'array',
            'position' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
