<?php

declare(strict_types=1);

use App\Http\Controllers\Customer\CustomerAddressController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'customer.active',
    'verified',
])->group(function (): void {
    Route::get(
        '/',
        CustomerDashboardController::class,
    )->name('dashboard');

    Route::get(
        '/addresses',
        [CustomerAddressController::class, 'index'],
    )->name('addresses.index');

    Route::get(
        '/addresses/create',
        [CustomerAddressController::class, 'create'],
    )->name('addresses.create');

    Route::post(
        '/addresses',
        [CustomerAddressController::class, 'store'],
    )->name('addresses.store');

    Route::get(
        '/addresses/{address}/edit',
        [CustomerAddressController::class, 'edit'],
    )
        ->whereNumber('address')
        ->name('addresses.edit');

    Route::put(
        '/addresses/{address}',
        [CustomerAddressController::class, 'update'],
    )
        ->whereNumber('address')
        ->name('addresses.update');

    Route::patch(
        '/addresses/{address}/default',
        [CustomerAddressController::class, 'setDefault'],
    )
        ->whereNumber('address')
        ->name('addresses.default');

    Route::delete(
        '/addresses/{address}',
        [CustomerAddressController::class, 'destroy'],
    )
        ->whereNumber('address')
        ->name('addresses.destroy');
    Route::get(
        '/orders',
        [
            CustomerOrderController::class,
            'index',
        ],
    )->name(
        'orders.index',
    );

    Route::get(
        '/orders/{order}',
        [
            CustomerOrderController::class,
            'show',
        ],
    )
        ->whereNumber(
            'order',
        )
        ->name(
            'orders.show',
        );

    Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');

    Route::get(
        '/invoices/{invoice}',
        [
            InvoiceController::class,
            'show',
        ],
    )
        ->whereNumber('invoice')
        ->name(
            'invoices.show',
        );

});
