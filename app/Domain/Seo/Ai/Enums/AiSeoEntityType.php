<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Enums;

enum AiSeoEntityType: string
{
    case Product = 'product';
    case Category = 'category';
    case Brand = 'brand';
    case Page = 'page';
}
