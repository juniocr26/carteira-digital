<?php

namespace App\UseCases;

use App\DTO\CompraSaldoDTO;
use App\Enums\SituacaoTransacaoEnum;
use CriptoLib\Crypto;
use App\DTO\ResponseDTO;
use App\Repository\CompraSaldoRepository;
use App\Repository\Interfaces\CompraSaldoRepositoryInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\BodyRequest;

class CompraSaldoUseCase
{
    public function __construct(
        private CompraSaldoRepositoryInterface $compraSaldoRepository = new CompraSaldoRepository(),
        private $crypto = new Crypto()
    ) {}

    public function realizaCompraSaldoCartaoCredito(array $request): ResponseDTO
    {
        try {
            $compraSaldo = $this->_criandoCompraSaldoCredito($request, SituacaoTransacaoEnum::PENDENTE_PAGAMENTO);
            $this->compraSaldoRepository->updateCompraSaldo($compraSaldo);

            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $compraSaldo->valor_compra * 100,
                'currency' => 'brl',
                'payment_method' => $compraSaldo->payment_method_id,
                'confirm' => true,
                'description' => "Compra de saldo para {$compraSaldo->nome} CPF: {$compraSaldo->cpf}",
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $compraSaldo->situacao_transacao = SituacaoTransacaoEnum::APROVADO;
                $compraSaldo->data_pagamento = now()->format('Y-m-d H:i:s');
                $this->compraSaldoRepository->updateCompraSaldo($compraSaldo);
            }
            return new ResponseDTO('sucesso', 'Compra com saldo atualizada com sucesso', $paymentIntent);

        } catch (\Throwable $th) {
            Log::error("Erro ao criar transação de compra de saldo: {$th->getMessage()} | {$th->getFile()} | linha: {$th->getLine()} | trace: {$th->getTraceAsString()}");
            return new ResponseDTO('erro', 'Não foi possível criar a transação de compra de saldo');
        }
    }

    public function criptografarDadosCompraParaRealizarVenda(Request $request): string
    {
        $jsonData = json_encode($request->all());
        $crypto = new Crypto();
        return $crypto->encrypt($jsonData);
    }

    public function realizarPostParaRotaComprarSaldoCredito(string $body)
    {
        $request = BodyRequest::create(
            route('compra.saldo.credito'), // URL fictícia, não importa
            'POST',
            ['body' => $body] // os dados que você quer enviar
        );

        return app()->call('App\Http\Controllers\CompraSaldoController@compraCredito', [
            'request' => $request
        ]);
    }

    private function _criandoCompraSaldoCredito(array $request, SituacaoTransacaoEnum $situacaoTransacaoEnum): CompraSaldoDTO
    {
        $objeto = $this->crypto->decrypt($request['body']);
        $objeto = json_decode($objeto);
        return new CompraSaldoDTO(
            $objeto->payment_method_id,
            $objeto->valor_compra,
            $situacaoTransacaoEnum,
            $objeto->nome,
            $objeto->cpf,
            now()->format('Y-m-d H:i:s')
        );
    }
}
