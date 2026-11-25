<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Domain\Seo\Data\EntitySeoData;
use App\Models\SeoMeta;

final class EntitySeoMapper
{
    public function fromModel(
        SeoMeta $meta,
    ): EntitySeoData {
        return new EntitySeoData(
            title: $meta->title,

            description: $meta->description,

            canonicalUrl: $meta->canonical_url,

            robotsIndex: $meta->robots_index,

            robotsFollow: $meta->robots_follow,

            ogTitle: $meta->og_title,

            ogDescription: $meta->og_description,

            ogImage: $meta->og_image,

            twitterTitle: $meta->twitter_title,

            twitterDescription: $meta->twitter_description,

            twitterImage: $meta->twitter_image,
        );
    }
}
