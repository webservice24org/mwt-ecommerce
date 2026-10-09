<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder\Registry;

use App\Domain\FooterBuilder\Data\FooterTemplateData;
use App\Domain\FooterBuilder\Enums\FooterTemplate;
use InvalidArgumentException;

final class FooterTemplateRegistry
{
    /**
     * @return list<FooterTemplateData>
     */
    public function all(): array
    {
        return array_map(
            fn (
                FooterTemplate $template,
            ): FooterTemplateData => $this->makeData(
                $template,
            ),
            FooterTemplate::cases(),
        );
    }

    public function default(): FooterTemplate
    {
        return FooterTemplate::LuxeNewsletter;
    }

    public function has(
        FooterTemplate $template,
    ): bool {
        return in_array(
            $template,
            FooterTemplate::cases(),
            true,
        );
    }

    public function supports(
        string $template,
    ): bool {
        return FooterTemplate::tryFrom(
            $template,
        ) !== null;
    }

    public function get(
        FooterTemplate $template,
    ): FooterTemplateData {
        if (
            ! $this->has(
                $template,
            )
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Footer template [%s] is not registered.',
                    $template->value,
                ),
            );
        }

        return $this->makeData(
            $template,
        );
    }

    public function getByKey(
        string $template,
    ): FooterTemplateData {
        $resolved =
            FooterTemplate::tryFrom(
                $template,
            );

        if (
            $resolved ===
            null
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Footer template [%s] is not registered.',
                    $template,
                ),
            );
        }

        return $this->get(
            $resolved,
        );
    }

    private function makeData(
        FooterTemplate $template,
    ): FooterTemplateData {
        return new FooterTemplateData(
            template: $template,

            key: $template->value,

            label: $template->label(),

            description: $template->description(),
        );
    }
}
