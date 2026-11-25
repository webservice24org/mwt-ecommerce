<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditHtmlImageObservationData;
use App\Domain\Seo\Audit\Data\SeoAuditHtmlLinkObservationData;
use App\Domain\Seo\Audit\Data\SeoAuditHtmlObservationData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

final class SeoAuditHtmlObservationParser
{
    public function parse(
        string $html,
    ): SeoAuditHtmlObservationData {
        if (! class_exists(DOMDocument::class)) {
            throw new RuntimeException(
                'PHP DOM extension is required for SEO audit HTML observation.',
            );
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="utf-8" ?>'.$html,
                LIBXML_NONET
                | LIBXML_NOERROR
                | LIBXML_NOWARNING,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded) {
            return new SeoAuditHtmlObservationData(
                title: null,
                metaDescription: null,
                canonicalReference: null,
                metaNoindex: false,
                h1Count: 0,
                wordCount: 0,
                links: [],
                images: [],
            );
        }

        $xpath = new DOMXPath($document);

        return new SeoAuditHtmlObservationData(
            title: $this->firstText(
                $xpath,
                '//title[1]',
            ),
            metaDescription: $this->metaContent(
                $xpath,
                'description',
            ),
            canonicalReference: $this->canonicalReference(
                $xpath,
            ),
            metaNoindex: $this->hasNoindexMeta(
                $xpath,
            ),
            h1Count: $this->nodeCount(
                $xpath,
                '//h1',
            ),
            wordCount: $this->wordCount(
                $document,
            ),
            links: $this->links(
                $xpath,
            ),
            images: $this->images(
                $xpath,
            ),
        );
    }

    private function nodeCount(
        DOMXPath $xpath,
        string $expression,
    ): int {
        $nodes = $xpath->query($expression);

        if ($nodes === false) {
            return 0;
        }

        return $nodes->length;
    }

    private function firstText(
        DOMXPath $xpath,
        string $expression,
    ): ?string {
        $nodes = $xpath->query($expression);

        if ($nodes === false) {
            return null;
        }

        $node = $nodes->item(0);

        if (! $node instanceof DOMNode) {
            return null;
        }

        return $this->normalizeText(
            $node->textContent,
        );
    }

    private function metaContent(
        DOMXPath $xpath,
        string $name,
    ): ?string {
        $nodes = $xpath->query('//meta[@name]');

        if ($nodes === false) {
            return null;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (
                strtolower(
                    trim(
                        $node->getAttribute('name'),
                    ),
                ) !== $name
            ) {
                continue;
            }

            return $this->normalizeText(
                $node->getAttribute('content'),
            );
        }

        return null;
    }

    private function canonicalReference(
        DOMXPath $xpath,
    ): ?string {
        $nodes = $xpath->query('//link[@href][@rel]');

        if ($nodes === false) {
            return null;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (
                ! in_array(
                    'canonical',
                    $this->tokens(
                        $node->getAttribute('rel'),
                    ),
                    true,
                )
            ) {
                continue;
            }

            $href = trim(
                $node->getAttribute('href'),
            );

            return $href === ''
                ? null
                : $href;
        }

        return null;
    }

    private function hasNoindexMeta(
        DOMXPath $xpath,
    ): bool {
        $nodes = $xpath->query('//meta[@name]');

        if ($nodes === false) {
            return false;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $name = strtolower(
                trim(
                    $node->getAttribute('name'),
                ),
            );

            if (
                ! in_array(
                    $name,
                    ['robots', 'googlebot'],
                    true,
                )
            ) {
                continue;
            }

            if (
                in_array(
                    'noindex',
                    $this->tokens(
                        $node->getAttribute('content'),
                    ),
                    true,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /** @return list<SeoAuditHtmlLinkObservationData> */
    private function links(
        DOMXPath $xpath,
    ): array {
        $nodes = $xpath->query('//a[@href]');

        if ($nodes === false) {
            return [];
        }

        $links = [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $reference = trim(
                $node->getAttribute('href'),
            );

            if ($reference === '') {
                continue;
            }

            $links[] =
                new SeoAuditHtmlLinkObservationData(
                    reference: $reference,
                    anchorText: $this->normalizeText(
                        $node->textContent,
                    ),
                    nofollow: in_array(
                        'nofollow',
                        $this->tokens(
                            $node->getAttribute('rel'),
                        ),
                        true,
                    ),
                );
        }

        return $links;
    }

    /** @return list<SeoAuditHtmlImageObservationData> */
    private function images(
        DOMXPath $xpath,
    ): array {
        $nodes = $xpath->query('//img[@src]');

        if ($nodes === false) {
            return [];
        }

        /** @var array<string, array{reference:string,alt_present:bool,alt_text:string|null,count:int}> $grouped */
        $grouped = [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $reference = trim(
                $node->getAttribute('src'),
            );

            if ($reference === '') {
                continue;
            }

            $altPresent =
                $node->hasAttribute('alt');

            $altText = $altPresent
                ? $this->normalizeText(
                    $node->getAttribute('alt'),
                    preserveEmpty: true,
                )
                : null;

            $key = hash(
                'sha256',
                implode(
                    "\0",
                    [
                        $reference,
                        $altPresent ? '1' : '0',
                        $altText ?? '',
                    ],
                ),
            );

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'reference' => $reference,
                    'alt_present' => $altPresent,
                    'alt_text' => $altText,
                    'count' => 0,
                ];
            }

            $grouped[$key]['count']++;
        }

        $images = [];

        foreach ($grouped as $item) {
            $images[] =
                new SeoAuditHtmlImageObservationData(
                    reference: $item['reference'],
                    altAttributePresent: $item['alt_present'],
                    altText: $item['alt_text'],
                    occurrences: $item['count'],
                );
        }

        return $images;
    }

    private function wordCount(
        DOMDocument $document,
    ): int {
        foreach (
            ['script', 'style', 'noscript', 'template', 'svg'] as $tag
        ) {
            $nodes = $document
                ->getElementsByTagName($tag);

            while ($nodes->length > 0) {
                $node = $nodes->item(0);

                if (
                    ! $node instanceof DOMNode
                    || $node->parentNode === null
                ) {
                    break;
                }

                $node->parentNode->removeChild($node);
            }
        }

        $body = $document
            ->getElementsByTagName('body')
            ->item(0);

        if (! $body instanceof DOMNode) {
            return 0;
        }

        $text = $this->normalizeText(
            $body->textContent,
        );

        if ($text === null || $text === '') {
            return 0;
        }

        $words = preg_split(
            '/\s+/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        return is_array($words)
            ? count($words)
            : 0;
    }

    /** @return list<string> */
    private function tokens(
        string $value,
    ): array {
        $parts = preg_split(
            '/[\s,]+/',
            strtolower(
                trim($value),
            ),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        return is_array($parts)
            ? $parts
            : [];
    }

    private function normalizeText(
        string $value,
        bool $preserveEmpty = false,
    ): ?string {
        $normalized = preg_replace(
            '/\s+/u',
            ' ',
            trim($value),
        );

        if (! is_string($normalized)) {
            return null;
        }

        if ($normalized === '' && ! $preserveEmpty) {
            return null;
        }

        return $normalized;
    }
}
