<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Services;

use App\Domain\Seo\Performance\Enums\SeoPerformanceCapability;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Models\Admin;
use App\Models\SeoPerformanceConnection;
use DateTimeZone;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class SeoPerformanceConnectionManager
{
    public function __construct(
        private SeoPerformanceCapabilityRegistry $capabilities,
    ) {}

    public function register(
        SeoPerformanceProvider $provider,
        string $displayName,
        string $propertyIdentifier,
        ?string $propertyUrl,
        string $timezone,
        ?Admin $admin = null,
    ): SeoPerformanceConnection {
        $displayName = trim($displayName);
        $propertyIdentifier = trim($propertyIdentifier);
        $propertyUrl = $propertyUrl === null ? null : trim($propertyUrl);
        $timezone = trim($timezone);

        if ($displayName === '' || mb_strlen($displayName) > 120) {
            throw new DomainException('A valid performance connection display name is required.');
        }

        if ($propertyIdentifier === '' || mb_strlen($propertyIdentifier) > 255) {
            throw new DomainException('A valid provider property identifier is required.');
        }

        if ($propertyUrl !== null && $propertyUrl !== '') {
            $scheme = strtolower((string) parse_url($propertyUrl, PHP_URL_SCHEME));
            $host = (string) parse_url($propertyUrl, PHP_URL_HOST);

            if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
                throw new DomainException('Performance property URLs must use HTTP or HTTPS.');
            }
        } else {
            $propertyUrl = null;
        }

        try {
            new DateTimeZone($timezone);
        } catch (Throwable) {
            throw new DomainException('A valid IANA timezone is required.');
        }

        /** @var list<string> $capabilities */
        $capabilities = array_map(
            static fn (SeoPerformanceCapability $capability): string => $capability->value,
            $this->capabilities->forProvider($provider),
        );

        return DB::transaction(static function () use (
            $provider,
            $displayName,
            $propertyIdentifier,
            $propertyUrl,
            $timezone,
            $capabilities,
            $admin,
        ): SeoPerformanceConnection {
            return SeoPerformanceConnection::query()->create([
                'public_id' => Str::uuid()->toString(),
                'provider' => $provider,
                'status' => SeoPerformanceConnectionStatus::Configured,
                'display_name' => $displayName,
                'property_identifier' => $propertyIdentifier,
                'property_url' => $propertyUrl,
                'timezone' => $timezone,
                'capabilities' => $capabilities,
                'created_by_admin_id' => $admin?->getKey(),
                'created_by_name' => $admin?->name,
                'created_by_email' => $admin?->email,
            ]);
        });
    }

    public function recordSuccessfulVerification(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceConnection {
        return DB::transaction(function () use ($connection): SeoPerformanceConnection {
            $current = SeoPerformanceConnection::query()
                ->lockForUpdate()
                ->findOrFail($connection->getKey());

            $current->forceFill([
                'status' => SeoPerformanceConnectionStatus::Active,
                'verified_at' => now(),
                'last_error_code' => null,
                'last_error_message' => null,
            ])->save();

            return $current->refresh();
        });
    }

    public function recordVerificationFailure(
        SeoPerformanceConnection $connection,
        string $code,
        string $message,
    ): SeoPerformanceConnection {
        $code = Str::limit(trim($code), 80, '');
        $message = Str::limit(trim($message), 500, '');

        if ($code === '') {
            $code = 'verification_failed';
        }
        if ($message === '') {
            $message = 'Provider verification failed.';
        }

        return DB::transaction(function () use ($connection, $code, $message): SeoPerformanceConnection {
            $current = SeoPerformanceConnection::query()
                ->lockForUpdate()
                ->findOrFail($connection->getKey());

            $current->forceFill([
                'status' => SeoPerformanceConnectionStatus::Error,
                'last_error_code' => $code,
                'last_error_message' => $message,
            ])->save();

            return $current->refresh();
        });
    }

    public function pause(
        SeoPerformanceConnection $connection,
    ): SeoPerformanceConnection {
        return DB::transaction(function () use ($connection): SeoPerformanceConnection {
            $current = SeoPerformanceConnection::query()
                ->lockForUpdate()
                ->findOrFail($connection->getKey());

            $current->forceFill([
                'status' => SeoPerformanceConnectionStatus::Paused,
            ])->save();

            return $current->refresh();
        });
    }
}
