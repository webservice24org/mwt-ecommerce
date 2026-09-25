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
use App\Http\Controllers\Admin\Catalog\ProductImageController;
use App\Http\Controllers\Admin\Catalog\ProductVariantController;
use App\Http\Controllers\Admin\Catalog\ProductVideoController;
use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\Admin\PageBuilderProductSearchController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageSectionController;
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

    Route::resource('pages', PageController::class)->except(['show']);

    Route::get('pages/{page}/builder', PageBuilderController::class)->name('pages.builder');
    Route::get('pages/{page}/builder/products', PageBuilderProductSearchController::class)->name('pages.builder.products');

    Route::post('pages/{page}/sections', [PageSectionController::class, 'store'])->name('pages.sections.store');
    Route::put('pages/{page}/sections/reorder', [PageSectionController::class, 'reorder'])->name('pages.sections.reorder');
    Route::post('pages/{page}/sections/{section}/duplicate', [PageSectionController::class, 'duplicate'])->whereNumber('section')->name('pages.sections.duplicate');
    Route::put('pages/{page}/sections/{section}', [PageSectionController::class, 'update'])->whereNumber('section')->name('pages.sections.update');
    Route::delete('pages/{page}/sections/{section}', [PageSectionController::class, 'destroy'])->whereNumber('section')->name('pages.sections.destroy');

});

Route::middleware([
    'auth:admin',
    'admin.active',
    'admin.role:super_admin',
])->group(function (): void {
    Route::resource('admins', AdminController::class)->except(['show']);

});
