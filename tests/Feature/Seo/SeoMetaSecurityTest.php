<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoMetaSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_morph_identity_is_not_mass_assignable(): void
    {
        $seo =
            new SeoMeta;

        $seo->fill([
            'seoable_type' => 'Injected\\Model',

            'seoable_id' => 999999,

            'canonical_url' => 'https://example.com/safe',
        ]);

        $this->assertNull(
            $seo->seoable_type,
        );

        $this->assertNull(
            $seo->seoable_id,
        );

        $this->assertSame(
            'https://example.com/safe',
            $seo->canonical_url,
        );
    }
}
