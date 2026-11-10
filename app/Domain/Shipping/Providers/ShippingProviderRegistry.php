<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Providers;

use App\Domain\Shipping\Contracts\ShippingProvider;
use App\Models\ShippingMethod;
use InvalidArgumentException;
use LogicException;

final class ShippingProviderRegistry
{
    /**
     * @var array<string, ShippingProvider>
     */
    private array $providers = [];

    public function register(
        ShippingProvider $provider,
    ): void {
        $code = $this->normalize(
            $provider->code(),
        );

        if ($code === '') {
            throw new InvalidArgumentException(
                'A shipping provider code cannot be empty.',
            );
        }

        if (isset($this->providers[$code])) {
            throw new LogicException(
                sprintf(
                    'Shipping provider "%s" is already registered.',
                    $code,
                ),
            );
        }

        $this->providers[$code] =
            $provider;
    }

    public function has(
        string $code,
    ): bool {
        return isset(
            $this->providers[
                $this->normalize(
                    $code,
                )
            ],
        );
    }

    public function resolve(
        string $code,
    ): ShippingProvider {
        $normalizedCode =
            $this->normalize(
                $code,
            );

        $provider =
            $this->providers[
                $normalizedCode
            ] ?? null;

        if (! $provider instanceof ShippingProvider) {
            throw new InvalidArgumentException(
                sprintf(
                    'Shipping provider "%s" is not registered.',
                    $normalizedCode !== ''
                        ? $normalizedCode
                        : $code,
                ),
            );
        }

        return $provider;
    }

    public function resolveFor(
        ShippingMethod $shippingMethod,
    ): ShippingProvider {
        return $this->resolve(
            $shippingMethod->driver,
        );
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys(
            $this->providers,
        );
    }

    private function normalize(
        string $code,
    ): string {
        return strtolower(
            trim(
                $code,
            ),
        );
    }
}
