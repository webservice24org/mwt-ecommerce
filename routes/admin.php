<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\Auth\ShowLoginController;
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
    Route::post(
        '/logout',
        LogoutController::class,
    )->name('logout');
});

Route::middleware([
    'auth:admin',
    'admin.active',
    'admin.role:super_admin',
])->group(function (): void {
    Route::resource('admins', AdminController::class)
        ->except(['show']);
});
