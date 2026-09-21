<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\PageBuilder\Enums\PageType;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property PageType $type
 * @property string $title
 * @property string $slug
 * @property PageStatus $status
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $published_at
 */
final class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'slug',
        'status',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PageSection, $this>
     */
    public function sections(): HasMany
    {
        return $this
            ->hasMany(PageSection::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * @return HasMany<PageSection, $this>
     */
    public function enabledSections(): HasMany
    {
        return $this
            ->hasMany(PageSection::class)
            ->where('is_enabled', true)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where(
                'status',
                PageStatus::Published->value,
            )
            ->where(
                static function (Builder $query): void {
                    $query
                        ->whereNull('published_at')
                        ->orWhere(
                            'published_at',
                            '<=',
                            now(),
                        );
                },
            );
    }

    public function isPublished(): bool
    {
        if ($this->status !== PageStatus::Published) {
            return false;
        }

        return $this->published_at === null
            || $this->published_at->lessThanOrEqualTo(
                now(),
            );
    }
}
