<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Enums;

enum SeoAuditSubjectType: string
{
    case Url = 'url';
    case Product = 'product';
    case Category = 'category';
    case Brand = 'brand';
    case Page = 'page';
    case Image = 'image';
    case Link = 'link';
}
