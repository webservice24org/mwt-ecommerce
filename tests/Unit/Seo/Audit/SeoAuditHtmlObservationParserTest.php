<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Audit;

use App\Domain\Seo\Audit\Services\SeoAuditHtmlObservationParser;
use PHPUnit\Framework\TestCase;

final class SeoAuditHtmlObservationParserTest extends TestCase
{
    public function test_parser_extracts_metadata_links_images_and_visible_word_count(): void
    {
        $html = <<<'HTML'
<!doctype html>
<html>
<head>
    <title>  Widget   Product </title>
    <meta name="description" content=" Useful widget description. ">
    <meta name="robots" content="index, noindex, follow">
    <link rel="alternate canonical" href="/products/widget">
    <style>.hidden { display:none }</style>
</head>
<body>
    <h1>Widget</h1>
    <h1>Specifications</h1>
    <p>Useful visible product words here.</p>
    <a href="/category/widgets">Widgets</a>
    <a href="/category/widgets" rel="nofollow">Widgets nofollow</a>
    <img src="/storage/widget.jpg" alt="Widget photo">
    <img src="/storage/widget.jpg" alt="Widget photo">
    <img src="/storage/decorative.jpg" alt="">
    <img src="data:image/gif;base64,AAAA">
    <script>ignored script words should not count</script>
</body>
</html>
HTML;

        $result =
            (new SeoAuditHtmlObservationParser)
                ->parse($html);

        self::assertSame(
            'Widget Product',
            $result->title,
        );
        self::assertSame(
            'Useful widget description.',
            $result->metaDescription,
        );
        self::assertSame(
            '/products/widget',
            $result->canonicalReference,
        );
        self::assertTrue(
            $result->metaNoindex,
        );
        self::assertSame(2, $result->h1Count);
        self::assertGreaterThanOrEqual(
            7,
            $result->wordCount,
        );
        self::assertCount(2, $result->links);
        self::assertCount(3, $result->images);
        self::assertSame(
            2,
            $result->images[0]->occurrences,
        );
        self::assertTrue(
            $result->images[1]->altAttributePresent,
        );
        self::assertSame(
            '',
            $result->images[1]->altText,
        );
    }
}
