<?php

declare(strict_types=1);

namespace App\Domain\Seo\StructuredData\Contracts;

interface StructuredDataNode
{
    /**
     * @return array<string, mixed>
     */
    public function toStructuredData(): array;
}
