<?php

namespace App\UseCases;

use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;
use App\Adapters\RabbitMQ\ReprocessamentoComprasCartaoRabbitMQAdapter;
use App\DTO\SaldoDTO;
use App\DTO\TransacaoDTO;
use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;
use App\Repository\Interfaces\SaldoRepositoryInterface;
use App\Repository\SaldoRepository;
use CriptoLib\Crypto;
use App\DTO\ResponseDTO;
use App\Repository\TransacaoRepository;
use App\Repository\Interfaces\TransacaoRepositoryInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\BodyRequest;

class TransacaoUseCase
{
    public function __construct(
        private TransacaoRepositoryInterface $transacaoRepository = new TransacaoRepository(),
        private SaldoRepositoryInterface $saldoRepository = new SaldoRepository(),
        private ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoComprasCartaoRabbitMQAdapter = new ReprocessamentoComprasCartaoRabbitMQAdapter(),
        private $crypto = new Crypto()
    ) {}

    public function realizaCompraCartaoCredito(array $request): ResponseDTO
    {
        try {
            $transacaoDTO = $this->_criandoTransacao($request, SituacaoTransacaoEnum::PENDENTE_PAGAMENTO);
            $this->transacaoRepository->updateTransacao($transacaoDTO);

            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $transacaoDTO->valor_compra * 100,
                'currency' => 'brl',
                'payment_method' => $transacaoDTO->payment_method_id,
                'confirm' => true,
                'description' => $transacaoDTO->descricao_transacao,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::APROVADO;
                $transacaoDTO->data_pagamento = now()->format('Y-m-d H:i:s');
                $this->transacaoRepository->updateTransacao($transacaoDTO);

                $saldoDTO = $this->_criandoSaldo($transacaoDTO);
                $this->saldoRepository->updateSaldo($saldoDTO, $transacaoDTO->tipo_transacao);

                return new ResponseDTO('sucesso', 'Compra realizada com sucesso', null);
            }

            return new ResponseDTO('erro', 'Pagamento não concluído', null);

        } catch (\Stripe\Exception\CardException $e) {
            // Erro no cartão - não adianta reprocessar
            $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
            $transacaoDTO->descricao_transacao = "Cartão recusado para a compra de saldo do cliente {$transacaoDTO->nome} CPF: {$transacaoDTO->cpf}";
            $this->transacaoRepository->updateTransacao($transacaoDTO);
            Log::warning("Cartão recusado: {$e->getMessage()}");
            return new ResponseDTO('erro', 'Cartão recusado', null);

        } catch (\Stripe\Exception\RateLimitException|\Stripe\Exception\ApiConnectionException|\Stripe\Exception\ApiErrorException $e) {
            // Erros temporários - vale reprocessar
            $transacaoDTO->retentativa = ($transacaoDTO->retentativa + 1);
            $body = $this->crypto->encrypt($transacaoDTO->__toString());
            $this->reprocessamentoComprasCartaoRabbitMQAdapter->enfilerarReprocessamentoComprasCartao($body);
            Log::error("Erro temporário Stripe: {$e->getMessage()}");
            return new ResponseDTO('erro', 'Erro temporário, pode reprocessar', null);

        } catch (\Throwable $th) {
            // Erro inesperado - geralmente não reprocessável
            Log::error("Erro inesperado: {$th->getMessage()} | {$th->getFile()} | linha: {$th->getLine()}");
            return new ResponseDTO('erro', 'Erro inesperado', null);
        }
    }

    public function criptografarDadosCompraParaRealizarVenda(Request $request): string
    {
        $jsonData = json_encode($request->all());
        $crypto = new Crypto();
        return $crypto->encrypt($jsonData);
    }

    public function realizarPostParaRotaComprarSaldoCartaoCredito(string $body)
    {
        $request = BodyRequest::create(
            route('compra.cartao.credito'), // URL fictícia, não importa
            'POST',
            ['body' => $body] // os dados que você quer enviar
        );

        return app()->call('App\Http\Controllers\TransacaoController@compra_cartao_credito', [
            'request' => $request
        ]);
    }

    private function _criandoTransacao(array $request, SituacaoTransacaoEnum $situacaoTransacaoEnum): TransacaoDTO
    {
        $objeto = $this->crypto->decrypt($request['body']);
        $objeto = json_decode($objeto);
        $tipo_transacao = TipoTransacaoEnum::from($objeto->tipo_transacao);
        return new TransacaoDTO(
            $objeto->payment_method_id,
            $objeto->valor_compra,
            $situacaoTransacaoEnum,
            $objeto->descricao_transacao,
            $tipo_transacao,
            $objeto->nome,
            $objeto->cpf,
            now()->format('Y-m-d H:i:s')
        );
    }

    private function _criandoSaldo(TransacaoDTO $transacaoDTO): SaldoDTO
    {
        return new SaldoDTO(
            $transacaoDTO->cpf,
            $transacaoDTO->valor_compra,
            now()->format('Y-m-d H:i:s')
        );
    }
}
