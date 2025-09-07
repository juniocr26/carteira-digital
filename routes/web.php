<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransacaoController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('stripe')->name('stripe.')->group(function () {
    Route::get('/', [TransacaoController::class, 'stripeJs'])->name('index');
    Route::get('/pix', [TransacaoController::class, 'stripePix'])->name('pix');
    Route::post('/tokenizar', [TransacaoController::class, 'stripeTokenizar'])->name('tokenizar');
    Route::post('/status_transacoes_pendentes', [TransacaoController::class, 'status_transacoes_pendentes'])->name('status.transacoes.pendentes');
});
