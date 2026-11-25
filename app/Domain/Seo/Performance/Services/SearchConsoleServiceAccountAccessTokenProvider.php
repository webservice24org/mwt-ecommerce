<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Exceptions\SearchConsoleIntegrationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;
use OpenSSLAsymmetricKey;

final readonly class SearchConsoleServiceAccountAccessTokenProvider
{
    public const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    public const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function __construct(
        private SearchConsoleServiceAccountCredentialResolver $credentials,
    ) {}

    public function accessToken(): string
    {
        $credentials = $this->credentials->resolve();
        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];
        if ($credentials->privateKeyId !== null && $credentials->privateKeyId !== '') {
            $header['kid'] = $credentials->privateKeyId;
        }

        $claims = [
            'iss' => $credentials->clientEmail,
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $unsigned = $this->encodeJson($header).'.'.$this->encodeJson($claims);
        $key = openssl_pkey_get_private($credentials->privateKey);

        if (! $key instanceof OpenSSLAsymmetricKey) {
            throw new SearchConsoleIntegrationException(
                'credentials_invalid',
                'Search Console service-account private key is invalid.',
            );
        }

        $signature = '';
        if (! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new SearchConsoleIntegrationException(
                'authorization_failed',
                'Search Console authorization could not be prepared.',
            );
        }

        $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'MWT-SEO-Performance/1.0'])
                ->connectTimeout(5)
                ->timeout(10)
                ->post(self::TOKEN_URI, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);
        } catch (ConnectionException) {
            throw new SearchConsoleIntegrationException(
                'authorization_unavailable',
                'Google authorization is temporarily unavailable.',
            );
        }

        if (! $response->successful()) {
            throw new SearchConsoleIntegrationException(
                'authorization_failed',
                'Google rejected the Search Console service-account authorization request.',
            );
        }

        $data = $response->json();
        $accessToken = is_array($data) ? ($data['access_token'] ?? null) : null;
        $tokenType = is_array($data) ? ($data['token_type'] ?? null) : null;

        if (
            ! is_string($accessToken)
            || trim($accessToken) === ''
            || ($tokenType !== null && (! is_string($tokenType) || strcasecmp($tokenType, 'Bearer') !== 0))
        ) {
            throw new SearchConsoleIntegrationException(
                'authorization_failed',
                'Google returned an invalid Search Console authorization response.',
            );
        }

        return $accessToken;
    }

    /** @param array<string, int|string> $value */
    private function encodeJson(array $value): string
    {
        try {
            $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            throw new SearchConsoleIntegrationException(
                'authorization_failed',
                'Search Console authorization could not be prepared.',
            );
        }

        return $this->base64UrlEncode($json);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
