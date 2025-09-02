<?php

use App\Http\Controllers\CompraSaldoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('stripe')->name('stripe.')->group(function () {
    Route::get('/', [CompraSaldoController::class, 'stripeJs'])->name('index');
    Route::post('/tokenizar', [CompraSaldoController::class, 'stripeTokenizar'])->name('tokenizar');
});
