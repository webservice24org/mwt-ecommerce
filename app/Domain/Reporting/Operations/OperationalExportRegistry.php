<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Operations;

use App\Domain\Reporting\Data\OperationalExportDefinitionData;
use App\Domain\Reporting\Enums\OperationalExportType;
use InvalidArgumentException;

final class OperationalExportRegistry
{
    /**
     * @var array<string, OperationalExportDefinitionData>
     */
    private array $definitions = [];

    public function register(
        OperationalExportDefinitionData $definition,
    ): void {
        $this->definitions[
            $definition
                ->type
                ->value
        ] = $definition;
    }

    public function get(
        OperationalExportType $type,
    ): OperationalExportDefinitionData {
        $definition =
            $this->definitions[
                $type->value
            ] ??
            null;

        if (
            $definition === null
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Operational export [%s] is not registered.',
                    $type->value,
                ),
            );
        }

        return $definition;
    }

    public function has(
        OperationalExportType $type,
    ): bool {
        return isset(
            $this->definitions[
                $type->value
            ],
        );
    }

    /**
     * @return array<int, OperationalExportDefinitionData>
     */
    public function all(): array
    {
        return array_values(
            $this->definitions,
        );
    }
}
