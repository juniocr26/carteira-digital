<?php

use App\Http\Controllers\TransacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('stripe')->name('stripe.')->group(function () {
    Route::get('/', [TransacaoController::class, 'stripeJs'])->name('index');
    Route::get('/pix', [TransacaoController::class, 'stripePix'])->name('pix');
    Route::post('/tokenizar', [TransacaoController::class, 'stripeTokenizar'])->name('tokenizar');
});
