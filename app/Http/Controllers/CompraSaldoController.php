<?php

namespace App\Http\Controllers;

use App\UseCases\CompraSaldoUseCase;
use Illuminate\Http\Request;

class CompraSaldoController extends Controller
{
    public function compraCredito(Request $request, CompraSaldoUseCase $compraSaldoUseCase)
    {
        try {
            $result = $compraSaldoUseCase->realizaCompraSaldoCartaoCredito($request->all());
            if ($result->status == 'sucesso') { $statusCode = 201; }
            if ($result->status == 'erro') { $statusCode = 400; }

            return response()->json([
                'status' => $result->status,
                'message' => $result->message,
            ], $statusCode);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'erro',
                'message' => 'Erro interno do servidor, ocorreu um erro inesperado no servidor.'
            ], 500);
        }
    }
}
