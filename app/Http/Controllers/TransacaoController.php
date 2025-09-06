<?php

namespace App\Http\Controllers;

use App\Enums\TipoTransacaoEnum;
use CriptoLib\Crypto;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\BodyRequest;
use App\UseCases\TransacaoUseCase;

class TransacaoController extends Controller
{
    private TransacaoUseCase $transacaoUseCase;

    public function __construct(TransacaoUseCase $transacaoUseCase)
    {
        $this->transacaoUseCase = $transacaoUseCase;
    }

    public function stripeJs(): View
    {
        return view('stripeJs');
    }

    public function stripePix(): View
    {
        return view('stripePix');
    }

    public function stripeTokenizar(Request $request): JsonResponse
    {
        $tipo_transacao = TipoTransacaoEnum::from($request['tipo_transacao']);
        $body = $this->transacaoUseCase->criptografarDadosCompraParaRealizarVenda($request);
        return ($tipo_transacao == TipoTransacaoEnum::COMPRA_PIX)
            ? $this->transacaoUseCase->realizarPostParaRotaComprarPix($body)
            : $this->transacaoUseCase->realizarPostParaRotaComprarSaldoCartaoCredito($body);
    }

    public function compra_cartao_credito(BodyRequest $request): JsonResponse
    {
        try {
            $result = $this->transacaoUseCase->realizaCompraCartaoCredito($request->all());
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

    public function compra_pix(BodyRequest $request): JsonResponse
    {
        try {
            $result = $this->transacaoUseCase->realizarCompraPix($request->all());
            if ($result->status == 'sucesso') { $statusCode = 201; }
            if ($result->status == 'erro') { $statusCode = 400; }

            return response()->json([
                'status' => $result->status,
                'message' => $result->message,
                'content' => $result->content
            ], $statusCode);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'erro',
                'message' => 'Erro interno do servidor, ocorreu um erro inesperado no servidor.'
            ], 500);
        }
    }
}
