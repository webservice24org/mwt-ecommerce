<?php

declare(strict_types=1);

namespace App\Domain\Seo\Sitemap\Data;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class SitemapUrlData
{
    public function __construct(
        public string $url,
        public ?DateTimeImmutable $lastModified = null,
    ) {
        $this->assertValidUrl(
            $url,
        );
    }

    /**
     * @return array{
     *     url: string,
     *     last_modified: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,

            'last_modified' => $this
                ->lastModified
                ?->format(
                    DateTimeInterface::ATOM,
                ),
        ];
    }

    private function assertValidUrl(
        string $url,
    ): void {
        if (
            trim(
                $url,
            ) === ''
        ) {
            throw new InvalidArgumentException(
                'Sitemap URL cannot be empty.',
            );
        }

        if (
            filter_var(
                $url,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'Sitemap URL must be an absolute valid URL.',
            );
        }

        $scheme =
            strtolower(
                (string) parse_url(
                    $url,
                    PHP_URL_SCHEME,
                ),
            );

        if (
            ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                'Sitemap URL must use HTTP or HTTPS.',
            );
        }
    }
}
