<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $seoable_type
 * @property int $seoable_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $canonical_url
 * @property bool|null $robots_index
 * @property bool|null $robots_follow
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image
 * @property string|null $twitter_title
 * @property string|null $twitter_description
 * @property string|null $twitter_image
 */
final class SeoMeta extends Model
{
    protected $table =
        'seo_meta';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'canonical_url',
        'robots_index',
        'robots_follow',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',

            'robots_follow' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
