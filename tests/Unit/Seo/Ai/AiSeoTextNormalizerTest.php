<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai;

use App\Domain\Seo\Ai\Services\AiSeoTextNormalizer;
use PHPUnit\Framework\TestCase;

final class AiSeoTextNormalizerTest extends TestCase
{
    public function test_html_script_style_entities_and_whitespace_are_normalized(): void
    {
        $normalizer =
            new AiSeoTextNormalizer;

        self::assertSame(
            'Fast & reliable cable.',
            $normalizer->normalize(
                '<style>.bad{}</style><p> Fast&nbsp;&amp; <strong>reliable</strong> cable. </p><script>alert(1)</script>',
            ),
        );
    }

    public function test_long_text_is_bounded_deterministically(): void
    {
        $normalizer =
            new AiSeoTextNormalizer;

        $result =
            $normalizer->normalize(
                str_repeat(
                    'a',
                    50,
                ),
                10,
            );

        self::assertSame(
            'aaaaaaaaa…',
            $result,
        );
    }

    public function test_page_builder_extraction_omits_urls_images_colors_and_technical_values(): void
    {
        $normalizer =
            new AiSeoTextNormalizer;

        $text =
            $normalizer
                ->pageBuilderConfig([
                    'title' => 'Build Your Store',

                    'description' => '<p>Everything you need.</p>',

                    'background_image' => '/storage/hero.jpg',

                    'button' => [
                        'label' => 'Shop Now',

                        'url' => '/products',
                    ],

                    'background_color' => '#111827',
                ]);

        self::assertSame(
            'Build Your Store | Everything you need. | Shop Now',
            $text,
        );
    }
}
