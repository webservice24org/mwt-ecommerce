<?php

declare(strict_types=1);

namespace App\Domain\HeaderBuilder;

use App\Domain\HeaderBuilder\Enums\HeaderTemplate;

final class HeaderConfigDefaults
{
    /**
     * @return array<string, mixed>
     */
    public function for(
        HeaderTemplate $template,
    ): array {
        return match ($template) {
            HeaderTemplate::MegaMenu => $this->megaMenu(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function megaMenu(): array
    {
        return [
            'announcement' => [
                'enabled' => true,

                'badge' => 'New Drop',

                'message' => 'Summer Luxe Collection is live!',

                'promo_code' => 'SUMMER26',

                'promo_suffix' => 'for 15% off.',

                'links' => [
                    [
                        'label' => 'Track Order',

                        'url' => '#',
                    ],
                    [
                        'label' => 'Store Locator',

                        'url' => '#',
                    ],
                ],

                'currency_label' => 'USD ($)',
            ],

            'brand' => [
                'name' => 'MWT STORE',

                'accent' => '.',

                'home_url' => '/',

                /*
                 * Empty logo means the frontend may
                 * render the configured fallback mark.
                 */
                'logo_url' => null,

                'fallback_mark' => 'A',
            ],

            'search' => [
                'enabled' => true,

                'placeholder' => 'Search 20,000+ fashion products...',

                'suggestions_enabled' => true,

                'suggestion_heading' => 'Trending Searches',

                'trending_searches' => [
                    'Oversized Hoodie',
                    'Cargo Pants',
                    'Leather Boots',
                ],
            ],

            'actions' => [
                'wishlist' => [
                    'enabled' => true,

                    'url' => '#',
                ],

                'cart' => [
                    'enabled' => true,

                    'url' => '#',
                ],

                'account' => [
                    'enabled' => true,

                    'guest_login_url' => '#',

                    'guest_register_url' => '#',

                    'menu_links' => [
                        [
                            'label' => 'My Orders',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Account Settings',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Saved Addresses',

                            'url' => '#',
                        ],
                    ],
                ],
            ],

            'navigation' => [
                'enabled' => true,

                'mega_menu_label' => 'Categories',

                'links' => [
                    [
                        'label' => 'New Arrivals',

                        'url' => '#',

                        'style' => 'default',
                    ],
                    [
                        'label' => 'Best Sellers',

                        'url' => '#',

                        'style' => 'default',
                    ],
                    [
                        'label' => 'Accessories',

                        'url' => '#',

                        'style' => 'default',
                    ],
                    [
                        'label' => 'Electronics',

                        'url' => '#',

                        'style' => 'default',
                    ],
                    [
                        'label' => 'Sale',

                        'url' => '#',

                        'style' => 'highlight',
                    ],
                ],
            ],

            'mega_menu' => [
                'enabled' => true,

                'groups' => [
                    [
                        'heading' => "Women's Apparel",

                        'links' => [
                            [
                                'label' => 'Dresses & Jumpsuits',

                                'url' => '#',
                            ],
                            [
                                'label' => 'Jackets & Coats',

                                'url' => '#',
                            ],
                            [
                                'label' => 'Tops & Tees',

                                'url' => '#',
                            ],
                            [
                                'label' => 'Activewear',

                                'url' => '#',
                            ],
                        ],
                    ],

                    [
                        'heading' => "Men's Fashion",

                        'links' => [
                            [
                                'label' => 'Hoodies & Sweatshirts',

                                'url' => '#',
                            ],
                            [
                                'label' => 'Denim & Jeans',

                                'url' => '#',
                            ],
                            [
                                'label' => 'Shirts & Polo Shirts',

                                'url' => '#',
                            ],
                            [
                                'label' => 'Sneakers',

                                'url' => '#',
                            ],
                        ],
                    ],
                ],

                'promotion' => [
                    'enabled' => true,

                    'eyebrow' => 'Featured Promo',

                    'title' => 'Up to 40% Off Footwear',

                    'button_label' => 'Shop Sale',

                    'url' => '#',
                ],
            ],

            'mobile' => [
                'search_enabled' => true,

                'search_placeholder' => 'Search products...',

                'menu_links' => [
                    [
                        'label' => 'Categories',

                        'url' => '#',

                        'style' => 'primary',
                    ],
                    [
                        'label' => 'New Arrivals',

                        'url' => '#',

                        'style' => 'default',
                    ],
                    [
                        'label' => 'Best Sellers',

                        'url' => '#',

                        'style' => 'default',
                    ],
                    [
                        'label' => 'Sale Items',

                        'url' => '#',

                        'style' => 'highlight',
                    ],
                ],
            ],
        ];
    }
}
