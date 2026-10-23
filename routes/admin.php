<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\ShowLoginController;
use App\Http\Controllers\Admin\Catalog\AttributeController;
use App\Http\Controllers\Admin\Catalog\AttributeValueController;
use App\Http\Controllers\Admin\Catalog\BrandController;
use App\Http\Controllers\Admin\Catalog\CategoryController;
use App\Http\Controllers\Admin\Catalog\ProductController;
use App\Http\Controllers\Admin\Catalog\ProductDescriptionMediaController;
use App\Http\Controllers\Admin\Catalog\ProductImageController;
use App\Http\Controllers\Admin\Catalog\ProductVariantController;
use App\Http\Controllers\Admin\Catalog\ProductVideoController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\FooterBuilderController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\InvoiceSettingController;
use App\Http\Controllers\Admin\MailSettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageBuilderBrandSearchController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageBuilderImageController;
use App\Http\Controllers\Admin\PageBuilderProductSearchController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
use App\Http\Controllers\Admin\WebsiteSettings\HeaderBuilderController;
use App\Models\FooterSetting;
use App\Models\HeaderSetting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest:admin')->group(
    function (): void {
        Route::get(
            '/login',
            ShowLoginController::class,
        )->name('login');

        Route::post(
            '/login',
            [LoginController::class, 'store'],
        )
            ->middleware('throttle:admin-login')
            ->name('login.store');

        Route::get(
            '/forgot-password',
            [ForgotPasswordController::class, 'create'],
        )->name('password.request');

        Route::post(
            '/forgot-password',
            [ForgotPasswordController::class, 'store'],
        )
            ->middleware('throttle:3,1')
            ->name('password.email');

        Route::get(
            '/reset-password/{token}',
            [ResetPasswordController::class, 'create'],
        )->name('password.reset');

        Route::post(
            '/reset-password',
            [ResetPasswordController::class, 'store'],
        )->name('password.store');
    },
);

Route::middleware([
    'auth:admin',
    'admin.active',
])->group(function (): void {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('brands', BrandController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);

    Route::resource('attributes', AttributeController::class)->except(['show']);
    Route::post('attributes/{attribute}/values', [AttributeValueController::class, 'store'])->name('attributes.values.store');
    Route::put('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'update'])->name('attributes.values.update');
    Route::delete('attributes/{attribute}/values/{value}', [AttributeValueController::class, 'destroy'])->name('attributes.values.destroy');

    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
    Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');

    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::put('products/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('products.images.reorder');
    Route::put('products/{product}/images/{image}/featured', [ProductImageController::class, 'featured'])->whereNumber('image')->name('products.images.featured');
    Route::put('products/{product}/images/{image}', [ProductImageController::class, 'update'])->whereNumber('image')->name('products.images.update');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->whereNumber('image')->name('products.images.destroy');
    Route::post('products/{product}/video', [ProductVideoController::class, 'store'])->name('products.video.store');
    Route::delete('products/{product}/video', [ProductVideoController::class, 'destroy'])->name('products.video.destroy');

    Route::post('products/description-media', ProductDescriptionMediaController::class)->name('products.description-media.store');

    Route::resource('pages', PageController::class)->except(['show']);

    Route::get('pages/{page}/builder', PageBuilderController::class)->name('pages.builder');
    Route::get('pages/{page}/builder/products', PageBuilderProductSearchController::class)->name('pages.builder.products');
    Route::get('pages/{page}/builder/brands', PageBuilderBrandSearchController::class)->name('pages.builder.brands');
    Route::post('pages/{page}/builder/images', PageBuilderImageController::class)->name('pages.builder.images.store');

    Route::post('pages/{page}/sections', [PageSectionController::class, 'store'])->name('pages.sections.store');
    Route::put('pages/{page}/sections/reorder', [PageSectionController::class, 'reorder'])->name('pages.sections.reorder');
    Route::post('pages/{page}/sections/{section}/duplicate', [PageSectionController::class, 'duplicate'])->whereNumber('section')->name('pages.sections.duplicate');
    Route::put('pages/{page}/sections/{section}', [PageSectionController::class, 'update'])->whereNumber('section')->name('pages.sections.update');
    Route::delete('pages/{page}/sections/{section}', [PageSectionController::class, 'destroy'])->whereNumber('section')->name('pages.sections.destroy');

    /*
    |--------------------------------------------------------------------------
    | Website Settings — Footer Builder
    |--------------------------------------------------------------------------
    |
    | B.2 establishes the protected route contract.
    | The temporary actions below are replaced by
    | FooterBuilderController in B.3.
    |
    */

    Route::get('website-settings/footer-builder', [FooterBuilderController::class, 'edit'])->middleware('can:viewAny,'.FooterSetting::class)->name('website-settings.footer-builder.edit');
    Route::put('website-settings/footer-builder', [FooterBuilderController::class, 'update'])->middleware('can:updateAny,'.FooterSetting::class)->name('website-settings.footer-builder.update');

    Route::get('website-settings/header-builder', [HeaderBuilderController::class, 'edit'])->middleware('can:viewAny,'.HeaderSetting::class)->name('website-settings.header-builder.edit');
    Route::put('website-settings/header-builder', [HeaderBuilderController::class, 'update'])->middleware('can:updateAny,'.HeaderSetting::class)->name('website-settings.header-builder.update');

    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('invoices.show');
    Route::get('website-settings/invoice', [InvoiceSettingController::class, 'edit'])->name('website-settings.invoice.edit');
    Route::post('website-settings/invoice', [InvoiceSettingController::class, 'update'])->name('website-settings.invoice.update');

    Route::get('website-settings/mail', [MailSettingController::class, 'edit'])->name('website-settings.mail.edit');
    Route::post('website-settings/mail', [MailSettingController::class, 'update'])->name('website-settings.mail.update');

    Route::post('website-settings/mail/test', [MailSettingController::class, 'test'])->name('website-settings.mail.test');
    /*
    |--------------------------------------------------------------------------
    | Oerder processing
    |--------------------------------------------------------------------------
    */

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->whereNumber('order')->name('orders.confirm');
    Route::post('orders/{order}/start-processing', [OrderController::class, 'startProcessing'])->whereNumber('order')->name('orders.start-processing');
    Route::post('orders/{order}/ship', [OrderController::class, 'ship'])->whereNumber('order')->name('orders.ship');
    Route::post('orders/{order}/deliver', [OrderController::class, 'deliver'])->whereNumber('order')->name('orders.deliver');
    Route::post('orders/{order}/complete', [OrderController::class, 'complete'])->whereNumber('order')->name('orders.complete');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');

    /*
    |--------------------------------------------------------------------------
    | Customer Management
    |--------------------------------------------------------------------------
    */

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('customers.edit');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('customers.update');
    Route::patch('customers/{customer}/block', [CustomerController::class, 'block'])->whereNumber('customer')->name('customers.block');
    Route::patch('customers/{customer}/unblock', [CustomerController::class, 'unblock'])->whereNumber('customer')->name('customers.unblock');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->whereNumber('customer')->name('customers.destroy');

});

Route::middleware([
    'auth:admin',
    'admin.active',
    'admin.role:super_admin',
])->group(function (): void {
    Route::resource('admins', AdminController::class)->except(['show']);

});
