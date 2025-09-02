<?php

namespace App\Http\Controllers;

use CriptoLib\Crypto;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\BodyRequest;
use App\UseCases\CompraSaldoUseCase;

class CompraSaldoController extends Controller
{
    private CompraSaldoUseCase $compraSaldoUseCase;

    public function __construct(CompraSaldoUseCase $compraSaldoUseCase)
    {
        $this->compraSaldoUseCase = $compraSaldoUseCase;
    }

    public function stripeJs(): View
    {
        return view('stripeJs');
    }

    public function stripeTokenizar(Request $request)
    {
        $body = $this->compraSaldoUseCase->criptografarDadosCompraParaRealizarVenda($request);
        $this->compraSaldoUseCase->realizarPostParaRotaComprarSaldoCredito($body);
    }

    public function compraCredito(BodyRequest $request): JsonResponse
    {
        try {
            $result = $this->compraSaldoUseCase->realizaCompraSaldoCartaoCredito($request->all());
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
