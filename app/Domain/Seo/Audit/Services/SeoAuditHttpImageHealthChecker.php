<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditImageHealthResultData;
use App\Domain\Seo\Audit\Enums\SeoAuditImageHealthState;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;

final readonly class SeoAuditHttpImageHealthChecker
{
    public const CONNECT_TIMEOUT_SECONDS = 5;

    public const TIMEOUT_SECONDS = 10;

    public const USER_AGENT = 'MWT-SEO-Audit/1.0';

    public function __construct(
        private Factory $http,
        private SeoAuditUrlNormalizer $urls,
    ) {}

    public function check(
        string $imageUrl,
        string $canonicalHost,
    ): SeoAuditImageHealthResultData {
        $sameOrigin = $this->urls->normalizeInternal(
            reference: $imageUrl,
            canonicalHost: $canonicalHost,
        );

        if ($sameOrigin === null) {
            return new SeoAuditImageHealthResultData(
                state: SeoAuditImageHealthState::SkippedExternal,
                failureCode: 'external_image_unchecked',
                failureMessage: 'External image health is not fetched by the same-origin SEO audit checker.',
            );
        }

        try {
            $response = $this->http
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'image/*,*/*;q=0.1',
                ])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->withoutRedirecting()
                ->withOptions([
                    'stream' => true,
                ])
                ->get($sameOrigin);
        } catch (ConnectionException) {
            return new SeoAuditImageHealthResultData(
                state: SeoAuditImageHealthState::Broken,
                failureCode: 'connection_error',
                failureMessage: 'SEO audit could not connect to the image resource.',
            );
        }

        $status = $response->status();
        $contentType = trim($response->header('Content-Type'));
        $contentType = $contentType === '' ? null : $contentType;

        if ($status >= 300 && $status <= 399) {
            return new SeoAuditImageHealthResultData(
                state: SeoAuditImageHealthState::Redirected,
                httpStatus: $status,
                contentType: $contentType,
            );
        }

        if (
            $status >= 200
            && $status <= 299
            && $contentType !== null
            && str_starts_with(strtolower($contentType), 'image/')
        ) {
            return new SeoAuditImageHealthResultData(
                state: SeoAuditImageHealthState::Healthy,
                httpStatus: $status,
                contentType: $contentType,
            );
        }

        return new SeoAuditImageHealthResultData(
            state: SeoAuditImageHealthState::Broken,
            httpStatus: $status,
            contentType: $contentType,
            failureCode: $status >= 200 && $status <= 299
                ? 'non_image_content'
                : 'http_status',
            failureMessage: $status >= 200 && $status <= 299
                ? 'Image URL returned a successful response with a non-image content type.'
                : 'Image URL returned a non-success HTTP response.',
        );
    }
}
