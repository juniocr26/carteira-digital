<?php

use App\Http\Controllers\TransacaoController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas de API organizadas em groups aninhados:
|
*/

Route::aliasMiddleware('jwt.auth', JwtMiddleware::class);

Route::middleware('jwt.auth')->prefix('compra')->name('compra.')->group(function () {
    Route::post('/cartao_credito', [TransacaoController::class, 'compra_cartao_credito'])->name('cartao.credito');
    Route::post('/pix', [TransacaoController::class, 'compra_pix'])->name('pix');
});
