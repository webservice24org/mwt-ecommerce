<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai;

use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use PHPUnit\Framework\TestCase;

final class AiSeoSuggestionFieldBoundaryTest extends TestCase
{
    public function test_only_reviewable_text_metadata_fields_are_supported(): void
    {
        $fields =
            array_map(
                static fn (
                    AiSeoSuggestionField $field,
                ): string => $field->value,
                AiSeoSuggestionField::cases(),
            );

        self::assertSame(
            [
                'meta_title',
                'meta_description',
                'og_title',
                'og_description',
                'twitter_title',
                'twitter_description',
            ],
            $fields,
        );

        foreach (
            [
                'canonical_url',
                'robots_index',
                'robots_follow',
                'og_image',
                'twitter_image',
                'structured_data',
                'price',
                'stock_quantity',
                'status',
            ] as $forbidden
        ) {
            self::assertNotContains(
                $forbidden,
                $fields,
            );
        }
    }
}
