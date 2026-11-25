<?php

declare(strict_types=1);

namespace App\Domain\Seo\Performance\Enums;

enum SearchConsolePerformanceDimension: string
{
    case Query = 'query';
    case LandingPage = 'landing_page';
}
