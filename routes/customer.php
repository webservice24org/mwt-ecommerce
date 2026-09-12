<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware([
    'auth',
    'customer.active',
    'verified',
])->group(function (): void {
    Route::get(
        '/',
        function () {
            return Inertia::render(
                'Customer/Dashboard',
            );
        },
    )->name('dashboard');
});
