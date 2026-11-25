<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditFetchResultData;
use App\Domain\Seo\Audit\Exceptions\SeoAuditFetchException;
use DomainException;
use Illuminate\Http\Client\Factory;
use Psr\Http\Message\StreamInterface;

final readonly class SeoAuditHttpPageFetcher
{
    public const CONNECT_TIMEOUT_SECONDS = 5;

    public const TIMEOUT_SECONDS = 10;

    public const MAX_BODY_BYTES = 2000000;

    public const USER_AGENT = 'MWT-SEO-Audit/1.0';

    public function __construct(
        private Factory $http,
        private SeoAuditUrlNormalizer $urls,
    ) {}

    public function fetch(
        string $url,
        string $canonicalHost,
    ): SeoAuditFetchResultData {
        $normalized =
            $this
                ->urls
                ->normalizeInternal(
                    reference: $url,
                    canonicalHost: $canonicalHost,
                );

        if ($normalized === null) {
            throw new DomainException(
                'SEO audit HTTP fetches are restricted to the configured canonical origin.',
            );
        }

        $response =
            $this
                ->http
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.1',
                ])
                ->connectTimeout(
                    self::CONNECT_TIMEOUT_SECONDS,
                )
                ->timeout(
                    self::TIMEOUT_SECONDS,
                )
                ->withoutRedirecting()
                ->withOptions([
                    'stream' => true,
                ])
                ->get($normalized);

        $contentType =
            $this->nullableHeader(
                $response->header(
                    'Content-Type',
                ),
            );

        $contentLength =
            $this->contentLength(
                $response->header(
                    'Content-Length',
                ),
            );

        if (
            $contentLength !== null
            && $contentLength > self::MAX_BODY_BYTES
        ) {
            throw new SeoAuditFetchException(
                errorCode: 'body_too_large',
                message: 'SEO audit response body exceeds the configured size limit.',
            );
        }

        $body = null;
        $bodyBytes = 0;

        if (
            $contentType !== null
            && str_contains(
                strtolower($contentType),
                'text/html',
            )
        ) {
            $body = $this->readBoundedBody(
                $response
                    ->toPsrResponse()
                    ->getBody(),
            );
            $bodyBytes = strlen($body);
        }

        return new SeoAuditFetchResultData(
            status: $response->status(),
            contentType: $contentType,
            redirectLocation: $this->nullableHeader(
                $response->header(
                    'Location',
                ),
            ),
            xRobotsTag: $this->nullableHeader(
                $response->header(
                    'X-Robots-Tag',
                ),
            ),
            body: $body,
            bodyBytes: $bodyBytes,
        );
    }

    private function readBoundedBody(
        StreamInterface $stream,
    ): string {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $body = '';

        while (! $stream->eof()) {
            $chunk = $stream->read(65536);

            if ($chunk === '') {
                break;
            }

            if (
                strlen($body)
                    + strlen($chunk)
                > self::MAX_BODY_BYTES
            ) {
                throw new SeoAuditFetchException(
                    errorCode: 'body_too_large',
                    message: 'SEO audit response body exceeds the configured size limit.',
                );
            }

            $body .= $chunk;
        }

        return $body;
    }

    private function nullableHeader(
        string $value,
    ): ?string {
        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function contentLength(
        string $value,
    ): ?int {
        $value = trim($value);

        if (
            $value === ''
            || preg_match('/^\d+$/', $value) !== 1
        ) {
            return null;
        }

        return (int) $value;
    }
}
