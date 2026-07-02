<?php

use App\Http\Controllers\PassController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\Panel\BusinessController;
use App\Http\Controllers\Panel\ClientController;
use App\Http\Controllers\WalletWebServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('panel.clients.index'));

/*
|--------------------------------------------------------------------------
| Alta pública (el QR general del local apunta aquí)
|--------------------------------------------------------------------------
*/
Route::get('registro', [PublicRegistrationController::class, 'create'])->name('register.create');
Route::post('registro', [PublicRegistrationController::class, 'store'])->name('register.store');

/*
|--------------------------------------------------------------------------
| Panel administrativo (CRM)
|--------------------------------------------------------------------------
*/
Route::prefix('panel')->name('panel.')->group(function () {
    Route::get('negocio', [BusinessController::class, 'edit'])->name('business.edit');
    Route::put('negocio', [BusinessController::class, 'update'])->name('business.update');

    Route::get('clientes', [ClientController::class, 'index'])->name('clients.index');
    Route::get('clientes/crear', [ClientController::class, 'create'])->name('clients.create');
    Route::post('clientes', [ClientController::class, 'store'])->name('clients.store');
    Route::get('clientes/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::put('clientes/{client}', [ClientController::class, 'update'])->name('clients.update');
});

/*
|--------------------------------------------------------------------------
| Pase público (lo abre el cliente en su teléfono)
| Binding por serial_number en vez de id
|--------------------------------------------------------------------------
*/
Route::get('p/{pass:serial_number}', [PassController::class, 'show'])->name('pass.show');
Route::get('p/{pass:serial_number}/apple', [PassController::class, 'apple'])->name('pass.apple');
Route::get('p/{pass:serial_number}/google', [PassController::class, 'google'])->name('pass.google');

/*
|--------------------------------------------------------------------------
| Web service de Apple PassKit (lo llama Apple Wallet, sin sesión ni CSRF)
| webServiceURL del pase = APP_URL/wallet
|--------------------------------------------------------------------------
*/
Route::prefix('wallet/v1')->group(function () {
    Route::post('devices/{device}/registrations/{passType}/{serial}', [WalletWebServiceController::class, 'register']);
    Route::delete('devices/{device}/registrations/{passType}/{serial}', [WalletWebServiceController::class, 'unregister']);
    Route::get('devices/{device}/registrations/{passType}', [WalletWebServiceController::class, 'updatedPasses']);
    Route::get('passes/{passType}/{serial}', [WalletWebServiceController::class, 'latestPass']);
    Route::post('log', [WalletWebServiceController::class, 'log']);
});
