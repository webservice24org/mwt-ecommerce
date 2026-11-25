<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Data\EntitySeoData;
use PHPUnit\Framework\TestCase;

final class EntitySeoDataTest extends TestCase
{
    public function test_empty_data_has_no_overrides(): void
    {
        $data =
            EntitySeoData::empty();

        $this->assertFalse(
            $data->hasOverrides(),
        );

        $this->assertNull(
            $data->robotsIndex,
        );

        $this->assertNull(
            $data->robotsFollow,
        );
    }

    public function test_false_boolean_is_still_an_explicit_override(): void
    {
        $data =
            new EntitySeoData(
                title: null,

                description: null,

                canonicalUrl: null,

                robotsIndex: false,

                robotsFollow: null,

                ogTitle: null,

                ogDescription: null,

                ogImage: null,

                twitterTitle: null,

                twitterDescription: null,

                twitterImage: null,
            );

        $this->assertTrue(
            $data->hasOverrides(),
        );

        $this->assertFalse(
            $data->robotsIndex,
        );
    }

    public function test_to_array_preserves_nullable_inheritance_values(): void
    {
        $data =
            EntitySeoData::empty();

        $array =
            $data->toArray();

        $this->assertArrayHasKey(
            'robots_index',
            $array,
        );

        $this->assertArrayHasKey(
            'robots_follow',
            $array,
        );

        $this->assertNull(
            $array[
                'robots_index'
            ],
        );

        $this->assertNull(
            $array[
                'robots_follow'
            ],
        );
    }
}
