<?php

use App\Http\Controllers\CompraSaldoController;
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

Route::middleware('jwt.auth')->prefix('compra')->group(function () {
    Route::prefix('saldo')->group(function () {
        Route::post('/cartaoCredito', [CompraSaldoController::class, 'compraCredito'])->name('compra.saldo.credito');
    });
});
