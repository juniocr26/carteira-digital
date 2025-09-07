<?php

namespace App\Http\Controllers;

use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;
use App\Repository\Interfaces\StatusTransacoesPendentesRepositoryInterface;
use CriptoLib\Crypto;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\BodyRequest;
use App\UseCases\TransacaoUseCase;

class TransacaoController extends Controller
{
    private TransacaoUseCase $transacaoUseCase;
    private StatusTransacoesPendentesRepositoryInterface $statusTransacoesPendentesRepositoryInterface;

    public function __construct(TransacaoUseCase $transacaoUseCase, StatusTransacoesPendentesRepositoryInterface $statusTransacoesPendentesRepositoryInterface)
    {
        $this->transacaoUseCase = $transacaoUseCase;
        $this->statusTransacoesPendentesRepositoryInterface = $statusTransacoesPendentesRepositoryInterface;
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

    public function status_transacoes_pendentes(Request $request): JsonResponse
    {
        $transaction_id = $request['transaction_id'];
        $statusTransacoesPendentesDTO = $this->statusTransacoesPendentesRepositoryInterface->findTransaction($transaction_id);
        if(is_null($statusTransacoesPendentesDTO)){
            return response()->json([
                'status' => 'warning',
                'message' => 'Ainda processando',
            ], 201);
        }

        return response()->json([
            'status'    => ($statusTransacoesPendentesDTO->situacao_transacao == SituacaoTransacaoEnum::RECUSADO) ? 'erro' : 'sucesso',
            'message'   => ($statusTransacoesPendentesDTO->situacao_transacao == SituacaoTransacaoEnum::RECUSADO) ? 'Pagamento Recusado' : 'Pagamento Aprovado',
        ], 201);
    }

    public function compra_cartao_credito(BodyRequest $request): JsonResponse
    {
        try {
            $result = $this->transacaoUseCase->realizaCompraCartaoCredito($request->all());
            if ($result->status == 'sucesso' || $result->status == 'warning') { $statusCode = 201; }
            if ($result->status == 'erro') { $statusCode = 400; }

            return response()->json([
                'status' => $result->status,
                'message' => $result->message,
                'content' => $result->content ?? ''
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
