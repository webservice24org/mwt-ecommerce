<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Short descriptions support text formatting, not embedded content.
 * Rebuild HTML from a closed tag/attribute set; never return raw submitted HTML.
 */
final class ProductShortDescriptionContent
{
    private const BLOCKS = ['p', 'div', 'ul', 'ol', 'li', 'blockquote', 'h3', 'h4', 'h5', 'h6', 'pre'];

    private const INLINE = ['strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'sub', 'sup', 'code'];

    private const DISCARD = ['script', 'style', 'template', 'svg', 'math', 'iframe', 'object', 'embed', 'video', 'audio', 'source', 'picture', 'img', 'figure', 'table', 'form', 'button', 'input', 'select', 'textarea', 'noscript', 'head', 'link', 'meta', 'base'];

    public static function prepare(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // Preserve legacy plain text, including literal ampersands and line breaks.
        if (! self::containsMarkup($value)) {
            return trim($value);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$value.'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

        if (! $loaded) {
            return null;
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $html = trim(self::children($body));

        if (self::textFromHtml($html) === null) {
            return null;
        }

        return self::containsMarkup($html) ? $html : '<p>'.$html.'</p>';
    }

    /** Safe HTML for the single product page; legacy text uses React text rendering. */
    public static function richHtml(?string $value): ?string
    {
        return $value !== null && self::containsMarkup($value)
            ? self::prepare($value)
            : null;
    }

    /** Plain text for cards, storefront DTOs and metadata. */
    public static function plainText(?string $value): ?string
    {
        $prepared = self::prepare($value);

        if ($prepared === null) {
            return null;
        }

        return self::containsMarkup($prepared)
            ? self::textFromHtml($prepared)
            : $prepared;
    }

    private static function containsMarkup(string $value): bool
    {
        return preg_match('/<(?:\/?[a-z][^>]*|!--[\s\S]*?)>/i', $value) === 1;
    }

    private static function textFromHtml(string $html): ?string
    {
        $spaced = preg_replace('/<\/(?:p|div|ul|ol|li|blockquote|h[1-6]|pre)>|<(?:br|hr)\s*\/?\s*>/i', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($spaced), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)) ?? '');

        return $text === '' ? null : $text;
    }

    private static function children(DOMNode $parent): string
    {
        $html = '';

        foreach ($parent->childNodes as $child) {
            $html .= self::render($child);
        }

        return $html;
    }

    private static function render(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return self::escape($node->data);
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::DISCARD, true)) {
            return '';
        }

        if (in_array($tag, ['br', 'hr'], true)) {
            return '<'.$tag.'>';
        }

        $content = self::children($node);

        if ($tag === 'a') {
            $href = $node->getAttribute('href');

            return self::safeHref($href)
                ? '<a href="'.self::escape($href).'">'.$content.'</a>'
                : $content;
        }

        // A product already has its own H1; short-description headings start at H3.
        if (in_array($tag, ['h1', 'h2'], true)) {
            $tag = 'h3';
        }

        if (in_array($tag, self::BLOCKS, true)) {
            $style = '';

            if (preg_match('/^\s*text-align\s*:\s*(left|center|right|justify)\s*;?\s*$/i', $node->getAttribute('style'), $matches) === 1) {
                $style = ' style="text-align: '.strtolower($matches[1]).'"';
            }

            return '<'.$tag.$style.'>'.$content.'</'.$tag.'>';
        }

        if (in_array($tag, self::INLINE, true)) {
            return '<'.$tag.'>'.$content.'</'.$tag.'>';
        }

        return $content;
    }

    private static function safeHref(string $href): bool
    {
        if ($href === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $href) === 1) {
            return false;
        }

        if (str_starts_with($href, '#') || (str_starts_with($href, '/') && ! str_starts_with($href, '//'))) {
            return true;
        }

        $parts = parse_url($href);

        return is_array($parts)
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ($parts['host'] ?? '') !== ''
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
