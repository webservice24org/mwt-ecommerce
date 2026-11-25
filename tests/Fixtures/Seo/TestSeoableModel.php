<?php

declare(strict_types=1);

namespace Tests\Fixtures\Seo;

use App\Domain\Seo\Concerns\HasSeoMeta;
use App\Domain\Seo\Contracts\Seoable;
use Illuminate\Database\Eloquent\Model;

final class TestSeoableModel extends Model implements Seoable
{
    use HasSeoMeta;

    protected $table =
        'seo_test_entities';

    public $timestamps =
        false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];
}
