<?php

declare(strict_types=1);

namespace App\Domain\WebsiteSettings;

final class BrandDisplaySchema
{
    /**
     * @param  array<string, mixed>  $brand
     * @param  array<string, list<string>>  $errors
     * @return array<string, mixed>
     */
    public function normalize(array $brand, array &$errors): array
    {
        $result = [];
        $mode = $brand['display_mode'] ?? null;

        if ($mode !== null && ! in_array($mode, ['text', 'logo'], true)) {
            $errors['brand.display_mode'][] = 'Select brand name or logo image.';
        } elseif ($mode !== null) {
            $result['display_mode'] = $mode;
        }

        $url = $brand['logo_url'] ?? null;
        if (is_string($url)) {
            $url = trim($url);
        }
        if ($url === '') {
            $url = null;
        }
        if ($url !== null && (! is_string($url) || ! $this->safeUrl($url))) {
            $errors['brand.logo_url'][] = 'Use a local image path or an HTTP(S) image URL.';
            $url = null;
        }
        if ($mode === 'logo' && $url === null) {
            $errors['brand.logo_url'][] = 'Upload a logo or enter an image URL.';
        }
        if (array_key_exists('logo_url', $brand) || $mode === 'logo') {
            $result['logo_url'] = $url;
        }

        return $result;
    }

    private function safeUrl(string $url): bool
    {
        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f]/', $url) === 1 || str_contains($url, '\\')) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}
