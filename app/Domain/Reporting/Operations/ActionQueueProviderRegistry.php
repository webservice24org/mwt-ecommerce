<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

final class ActionQueueProviderRegistry
{
    /**
     * @var array<string, ActionQueueProvider>
     */
    private array $providers = [];

    public function register(
        ActionQueueProvider $provider,
    ): void {
        $this->providers[
            $provider
                ->source()
                ->value
        ] = $provider;
    }

    /**
     * @return array<int, ActionQueueProvider>
     */
    public function providers(): array
    {
        return array_values(
            $this->providers,
        );
    }
}
