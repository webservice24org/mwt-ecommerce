<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $site_name
 * @property string $title_separator
 * @property string $default_title_template
 * @property string|null $default_meta_description
 * @property string|null $canonical_host
 * @property bool $default_robots_index
 * @property bool $default_robots_follow
 * @property string|null $default_og_image
 * @property string $twitter_card
 * @property string|null $organization_name
 * @property string|null $organization_logo
 * @property array<int, string>|null $organization_social_profiles
 * @property string|null $google_site_verification
 * @property string|null $bing_site_verification
 * @property bool $enable_structured_data
 * @property bool $enable_open_graph
 * @property bool $enable_twitter_cards
 * @property bool $enable_sitemap
 */
final class SeoSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_name',
        'title_separator',
        'default_title_template',
        'default_meta_description',
        'canonical_host',
        'default_robots_index',
        'default_robots_follow',
        'default_og_image',
        'twitter_card',
        'organization_name',
        'organization_logo',
        'organization_social_profiles',
        'google_site_verification',
        'bing_site_verification',
        'enable_structured_data',
        'enable_open_graph',
        'enable_twitter_cards',
        'enable_sitemap',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_robots_index' => 'boolean',

            'default_robots_follow' => 'boolean',

            'organization_social_profiles' => 'array',

            'enable_structured_data' => 'boolean',

            'enable_open_graph' => 'boolean',

            'enable_twitter_cards' => 'boolean',

            'enable_sitemap' => 'boolean',
        ];
    }
}
