<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder\Enums;

enum FooterTemplate: string
{
    case LuxeNewsletter =
        'luxe_newsletter';

    case MinimalLocalized =
        'minimal_localized';

    case MarketplaceTrust =
        'marketplace_trust';

    public function label(): string
    {
        return match ($this) {
            self::LuxeNewsletter => 'Luxe Newsletter',

            self::MinimalLocalized => 'Minimal Localization',

            self::MarketplaceTrust => 'Marketplace Trust',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LuxeNewsletter => 'Newsletter-focused ecommerce footer with value propositions, payment methods, and link columns.',

            self::MinimalLocalized => 'Minimal dark footer with brand information, localization controls, and compact link groups.',

            self::MarketplaceTrust => 'Marketplace footer with promotion banner, category links, trust information, and certifications.',
        };
    }
}
