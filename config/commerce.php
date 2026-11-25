<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Store Currency
    |--------------------------------------------------------------------------
    |
    | Product prices are stored internally as integer minor units.
    |
    | MWT Ecommerce currently operates with BDT as its storefront currency.
    | Future multi-currency support must introduce a proper currency-aware
    | money model rather than changing structured-data formatting alone.
    |
    */

    'currency' => env(
        'STORE_CURRENCY',
        'BDT',
    ),
];
