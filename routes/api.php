<?php

use App\Http\Controllers\CompraSaldoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas de API organizadas em groups aninhados:
|
*/

Route::prefix('compra')->group(function () {
    Route::prefix('saldo')->group(function () {
        Route::post('/cartaoCredito', [CompraSaldoController::class, 'compraCredito']);
    });
});
