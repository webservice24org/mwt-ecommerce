<?php

declare(strict_types=1);

namespace App\Domain\Seo\Concerns;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeoMeta
{
    /**
     * @return MorphOne<SeoMeta, $this>
     */
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(
            SeoMeta::class,
            'seoable',
        );
    }
}
