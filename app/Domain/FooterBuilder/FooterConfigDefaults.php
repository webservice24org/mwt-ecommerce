<?php

declare(strict_types=1);

namespace App\Domain\FooterBuilder;

use App\Domain\FooterBuilder\Enums\FooterTemplate;

final class FooterConfigDefaults
{
    /**
     * @return array<string, mixed>
     */
    public function for(
        FooterTemplate $template,
    ): array {
        return match ($template) {
            FooterTemplate::LuxeNewsletter => $this->luxeNewsletter(),

            FooterTemplate::MinimalLocalized => $this->minimalLocalized(),

            FooterTemplate::MarketplaceTrust => $this->marketplaceTrust(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function luxeNewsletter(): array
    {
        return [
            'brand' => [
                'name' => 'LUXE STORE',

                'description' => null,
            ],

            'link_groups' => [
                [
                    'heading' => 'Shop Collections',

                    'links' => [
                        [
                            'label' => 'New Arrivals',

                            'url' => '#',
                        ],
                        [
                            'label' => "Women's Apparel",

                            'url' => '#',
                        ],
                        [
                            'label' => "Men's Fashion",

                            'url' => '#',
                        ],
                        [
                            'label' => 'Accessories & Bags',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Seasonal Clearance',

                            'url' => '#',
                        ],
                    ],
                ],

                [
                    'heading' => 'Customer Care',

                    'links' => [
                        [
                            'label' => 'Order Tracking',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Shipping & Delivery',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Returns & Exchanges',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Size Guide',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Help Center / FAQ',

                            'url' => '#',
                        ],
                    ],
                ],

                [
                    'heading' => 'Company',

                    'links' => [
                        [
                            'label' => 'About Luxe',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Careers',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Sustainability',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Press & Media',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Store Locator',

                            'url' => '#',
                        ],
                    ],
                ],
            ],

            'social_links' => [
                [
                    'platform' => 'instagram',

                    'url' => '#',
                ],
                [
                    'platform' => 'x',

                    'url' => '#',
                ],
                [
                    'platform' => 'facebook',

                    'url' => '#',
                ],
                [
                    'platform' => 'tiktok',

                    'url' => '#',
                ],
            ],

            'copyright' => [
                'name' => 'Luxe Store Inc.',

                'suffix' => 'All rights reserved.',
            ],

            'developer' => [
                'prefix' => 'Designed & Built by',

                'name' => 'PixelCraft Studio',

                'url' => '#',
            ],

            'value_props' => [
                [
                    'icon' => 'package',

                    'title' => 'Free Shipping',

                    'description' => 'On orders over $100 worldwide',
                ],
                [
                    'icon' => 'shield-check',

                    'title' => 'Secure Payment',

                    'description' => '100% encrypted transactions',
                ],
                [
                    'icon' => 'refresh-ccw',

                    'title' => '30-Day Returns',

                    'description' => 'Hassle-free return policy',
                ],
                [
                    'icon' => 'headphones',

                    'title' => '24/7 Support',

                    'description' => 'Dedicated customer team',
                ],
            ],

            'newsletter' => [
                'enabled' => true,

                'description' => 'Subscribe to our VIP newsletter to unlock 15% off your first purchase, exclusive drop notifications, and weekly style inspirations.',

                'placeholder' => 'Enter your email address',

                'button_label' => 'Subscribe',
            ],

            'payment_methods' => [
                'VISA',
                'MC',
                'PAYPAL',
                'APPLE PAY',
            ],

            'localization' => [
                'enabled' => false,

                'languages' => [],

                'currencies' => [],
            ],

            'promotion' => [
                'enabled' => false,

                'badge' => null,

                'message' => null,

                'code' => null,

                'button_label' => null,

                'button_url' => null,
            ],

            'popular_links' => [],

            'certifications' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalLocalized(): array
    {
        return [
            'brand' => [
                'name' => 'NORDIC // RAW',

                'description' => 'Minimalist apparel engineered for durability, utility, and timeless style. Designed in Oslo, Norway.',
            ],

            'link_groups' => [
                [
                    'heading' => 'Catalog',

                    'links' => [
                        [
                            'label' => 'All Products',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Outerwear',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Knitwear',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Footwear',

                            'url' => '#',
                        ],
                    ],
                ],

                [
                    'heading' => 'Support',

                    'links' => [
                        [
                            'label' => 'Contact Us',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Store Locator',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Returns Portal',

                            'url' => '#',
                        ],
                        [
                            'label' => 'FAQ',

                            'url' => '#',
                        ],
                    ],
                ],

                [
                    'heading' => 'Legal',

                    'links' => [
                        [
                            'label' => 'Privacy Policy',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Terms of Service',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Cookie Preferences',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Accessibility',

                            'url' => '#',
                        ],
                    ],
                ],
            ],

            'social_links' => [
                [
                    'platform' => 'instagram',

                    'url' => '#',
                ],
                [
                    'platform' => 'pinterest',

                    'url' => '#',
                ],
                [
                    'platform' => 'x',

                    'url' => '#',
                ],
                [
                    'platform' => 'youtube',

                    'url' => '#',
                ],
            ],

            'copyright' => [
                'name' => 'NORDIC RAW LTD.',

                'suffix' => 'All rights reserved.',
            ],

            'developer' => [
                'prefix' => 'Engineered by',

                'name' => 'Apex digital',

                'url' => '#',
            ],

            'value_props' => [],

            'newsletter' => [
                'enabled' => false,

                'description' => null,

                'placeholder' => null,

                'button_label' => null,
            ],

            'payment_methods' => [],

            'localization' => [
                'enabled' => true,

                'languages' => [
                    [
                        'code' => 'en-US',

                        'label' => 'English (US)',
                    ],
                    [
                        'code' => 'no',

                        'label' => 'Norsk',
                    ],
                    [
                        'code' => 'de',

                        'label' => 'Deutsch',
                    ],
                    [
                        'code' => 'fr',

                        'label' => 'Français',
                    ],
                ],

                'currencies' => [
                    [
                        'code' => 'USD',

                        'label' => 'USD ($)',
                    ],
                    [
                        'code' => 'EUR',

                        'label' => 'EUR (€)',
                    ],
                    [
                        'code' => 'GBP',

                        'label' => 'GBP (£)',
                    ],
                    [
                        'code' => 'NOK',

                        'label' => 'NOK (kr)',
                    ],
                ],
            ],

            'promotion' => [
                'enabled' => false,

                'badge' => null,

                'message' => null,

                'code' => null,

                'button_label' => null,

                'button_url' => null,
            ],

            'popular_links' => [],

            'certifications' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function marketplaceTrust(): array
    {
        return [
            'brand' => [
                'name' => 'MarketHub Global Inc.',

                'description' => null,
            ],

            'link_groups' => [
                [
                    'heading' => 'Marketplace',

                    'links' => [
                        [
                            'label' => 'Featured Sellers',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Daily Deals',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Trending Products',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Gift Cards',

                            'url' => '#',
                        ],
                    ],
                ],

                [
                    'heading' => 'Sell On Platform',

                    'links' => [
                        [
                            'label' => 'Become a Vendor',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Affiliate Program',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Merchant Portal',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Fulfillment Rules',

                            'url' => '#',
                        ],
                    ],
                ],

                [
                    'heading' => 'Trust & Safety',

                    'links' => [
                        [
                            'label' => 'Buyer Protection',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Verified Merchants',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Report Item',

                            'url' => '#',
                        ],
                        [
                            'label' => 'Security Center',

                            'url' => '#',
                        ],
                    ],
                ],
            ],

            'social_links' => [
                [
                    'platform' => 'instagram',

                    'url' => '#',
                ],
                [
                    'platform' => 'x',

                    'url' => '#',
                ],
                [
                    'platform' => 'facebook',

                    'url' => '#',
                ],
            ],

            'copyright' => [
                'name' => 'MarketHub Global Inc.',

                'suffix' => 'All rights reserved.',
            ],

            'developer' => [
                'prefix' => 'Created by',

                'name' => 'Nexus Labs',

                'url' => '#',
            ],

            'value_props' => [],

            'newsletter' => [
                'enabled' => false,

                'description' => null,

                'placeholder' => null,

                'button_label' => null,
            ],

            'payment_methods' => [],

            'localization' => [
                'enabled' => false,

                'languages' => [],

                'currencies' => [],
            ],

            'promotion' => [
                'enabled' => true,

                'badge' => 'Flash Sale',

                'message' => 'Get 20% OFF your first order with code',

                'code' => 'WELCOME20',

                'button_label' => 'Shop Now',

                'button_url' => '#',
            ],

            'popular_links' => [
                [
                    'label' => 'Sneakers',

                    'url' => '#',
                ],
                [
                    'label' => 'Smartwatches',

                    'url' => '#',
                ],
                [
                    'label' => 'Denim Jackets',

                    'url' => '#',
                ],
                [
                    'label' => 'Wireless Earbuds',

                    'url' => '#',
                ],
                [
                    'label' => 'Backpacks',

                    'url' => '#',
                ],
                [
                    'label' => 'Sunglasses',

                    'url' => '#',
                ],
            ],

            'certifications' => [
                'SSL 256-Bit Encrypted',
                'PCI-DSS Level 1 Compliant',
            ],
        ];
    }
}
