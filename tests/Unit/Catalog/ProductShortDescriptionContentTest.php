<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Services\ProductShortDescriptionContent;
use PHPUnit\Framework\TestCase;

final class ProductShortDescriptionContentTest extends TestCase
{
    public function test_legacy_text_is_preserved_and_blank_content_is_null(): void
    {
        $text = "5 < 10 & good\nSecond line";

        self::assertSame($text, ProductShortDescriptionContent::prepare($text));
        self::assertSame($text, ProductShortDescriptionContent::plainText($text));
        self::assertNull(ProductShortDescriptionContent::richHtml($text));
        self::assertNull(ProductShortDescriptionContent::prepare('<p><br></p>'));
    }

    public function test_formatting_is_retained_but_excerpts_are_plain_text(): void
    {
        $html = '<p><strong>Fast &amp; durable</strong></p><ul><li>USB-C</li><li>বাংলা</li></ul>';

        self::assertSame($html, ProductShortDescriptionContent::prepare($html));
        self::assertSame($html, ProductShortDescriptionContent::richHtml($html));
        self::assertSame('Fast & durable USB-C বাংলা', ProductShortDescriptionContent::plainText($html));
    }

    public function test_only_safe_markup_and_links_survive(): void
    {
        $html = '<h1 onclick="alert(1)">Heading</h1><p style="position:fixed">Text <a href="javascript:alert(1)">bad</a> <a href="/products">shop</a></p>'
            .'<script>alert(1)</script><svg onload="alert(1)"></svg><img src="x" onerror="alert(1)"><iframe src="https://example.com"></iframe>'
            .'<table><tr><td>Not summary content</td></tr></table><p style="text-align: center">Center</p>';
        $clean = ProductShortDescriptionContent::prepare($html);

        self::assertSame('<h3>Heading</h3><p>Text bad <a href="/products">shop</a></p><p style="text-align: center">Center</p>', $clean);
        self::assertSame($clean, ProductShortDescriptionContent::prepare($clean));
        self::assertSame('Heading Text bad shop Center', ProductShortDescriptionContent::plainText($clean));
    }

    public function test_external_unsafe_protocols_and_credentials_are_removed(): void
    {
        $html = '<p><a href="//evil.test">one</a> <a href="https://user@evil.test">two</a> <a href="data:text/html,x">three</a> <a href="https://example.com/path?a=1&amp;b=2">four</a></p>';

        self::assertSame('<p>one two three <a href="https://example.com/path?a=1&amp;b=2">four</a></p>', ProductShortDescriptionContent::prepare($html));
    }
}
