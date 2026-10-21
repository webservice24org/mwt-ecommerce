<?php

declare(strict_types=1);

use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\CheckoutAddressController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\CheckoutOrderController;
use App\Http\Controllers\Frontend\CheckoutValidationController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController as StorefrontPageController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\NewsletterSubscriberController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Storefront
|--------------------------------------------------------------------------
*/

Route::get(
    '/',
    [HomeController::class, 'index'],
)->name('home');

Route::get(
    '/products',
    [ProductController::class, 'index'],
)->name('products.index');

Route::get(
    '/products/search-suggestions',
    [ProductController::class, 'searchSuggestions'],
)->name('products.search-suggestions');

/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/

Route::get(
    '/cart',
    [CartController::class, 'show'],
)->name('cart.show');

Route::post(
    '/cart/items',
    [CartController::class, 'store'],
)->name('cart.items.store');

Route::patch(
    '/cart/items/{key}',
    [CartController::class, 'update'],
)->name('cart.items.update');

Route::delete(
    '/cart/items/{key}',
    [CartController::class, 'destroy'],
)->name('cart.items.destroy');

Route::delete(
    '/cart',
    [CartController::class, 'clear'],
)->name('cart.clear');

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'customer.active',
    'verified',
])->group(function (): void {
    Route::get(
        '/checkout',
        CheckoutController::class,
    )->name('checkout.index');

    Route::post(
        '/checkout/address',
        [
            CheckoutAddressController::class,
            'select',
        ],
    )
        ->middleware(
            'throttle:30,1',
        )
        ->name(
            'checkout.address.select',
        );

    Route::post(
        '/checkout/addresses',
        [
            CheckoutAddressController::class,
            'store',
        ],
    )
        ->middleware(
            'throttle:10,1',
        )
        ->name(
            'checkout.addresses.store',
        );

    Route::post(
        '/checkout/validate',
        CheckoutValidationController::class,
    )
        ->middleware(
            'throttle:10,1',
        )
        ->name(
            'checkout.validate',
        );

    Route::post(
        '/checkout/order',
        CheckoutOrderController::class,
    )
        ->middleware(
            'throttle:5,1',
        )
        ->name(
            'checkout.order.store',
        );
});

/*
|--------------------------------------------------------------------------
| Storefront Catalog
|--------------------------------------------------------------------------
*/

Route::get(
    '/products/{slug}',
    [ProductController::class, 'show'],
)->name('products.show');

Route::get(
    '/category/{slug}',
    [CategoryController::class, 'show'],
)->name('categories.show');

Route::get(
    '/pages/{slug}',
    StorefrontPageController::class,
)->name('frontend.pages.show');

/*
|--------------------------------------------------------------------------
| Wishlist
|--------------------------------------------------------------------------
*/

Route::get(
    '/wishlist',
    [WishlistController::class, 'index'],
)->name('wishlist.index');

Route::post(
    '/wishlist/{product}/toggle',
    [WishlistController::class, 'toggle'],
)
    ->whereNumber(
        'product',
    )
    ->name(
        'wishlist.toggle',
    );

Route::delete(
    '/wishlist/{product}',
    [WishlistController::class, 'destroy'],
)
    ->whereNumber(
        'product',
    )
    ->name(
        'wishlist.destroy',
    );

Route::delete(
    '/wishlist',
    [WishlistController::class, 'clear'],
)->name('wishlist.clear');

/*
|--------------------------------------------------------------------------
| Newsletter
|--------------------------------------------------------------------------
*/

Route::post(
    '/newsletter/subscribe',
    [
        NewsletterSubscriberController::class,
        'store',
    ],
)
    ->middleware(
        'throttle:5,1',
    )
    ->name(
        'newsletter.subscribe',
    );

/*
|--------------------------------------------------------------------------
| Customer Profile
|--------------------------------------------------------------------------
*/

Route::middleware(
    'auth',
)->group(function (): void {
    Route::get(
        '/profile',
        [ProfileController::class, 'edit'],
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update'],
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy'],
    )->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Legacy Customer Dashboard URL
|--------------------------------------------------------------------------
|
| Customer account routes live in routes/customer.php and are mounted
| by bootstrap/app.php under /account with the customer.* route prefix.
|
*/

Route::get(
    '/dashboard',
    static fn () => redirect()
        ->route(
            'customer.dashboard',
        ),
)
    ->middleware([
        'auth',
        'customer.active',
        'verified',
    ])
    ->name(
        'dashboard',
    );

require __DIR__.'/auth.php';
