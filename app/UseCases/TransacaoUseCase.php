<?php

namespace App\UseCases;

use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;
use App\DTO\SaldoDTO;
use App\DTO\TransacaoDTO;
use App\DTO\ResponseDTO;
use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;
use App\Repository\Interfaces\SaldoRepositoryInterface;
use App\Repository\Interfaces\TransacaoRepositoryInterface;
use CriptoLib\Crypto;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\BodyRequest;

class TransacaoUseCase
{
    public function __construct(
        private TransacaoRepositoryInterface $transacaoRepository,
        private SaldoRepositoryInterface $saldoRepository,
        private ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoAdapter,
        private Crypto $crypto
    ) {}

    public function retentativaPagamento(TransacaoDTO $transacaoDTO): ResponseDTO
    {
        return $this->processarPagamento($transacaoDTO, true);
    }

    public function realizaCompraCartaoCredito(array $request): ResponseDTO
    {
        $transacaoDTO = $this->_criandoTransacao($request, SituacaoTransacaoEnum::PENDENTE_PAGAMENTO);
        $this->transacaoRepository->updateTransacao($transacaoDTO);

        return $this->processarPagamento($transacaoDTO, false);
    }

    private function processarPagamento(TransacaoDTO $transacaoDTO, bool $isRetentativa): ResponseDTO
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = $isRetentativa && $transacaoDTO->payment_method_id
                ? $this->retriveAndConfirmPaymentIntent($transacaoDTO->payment_method_id)
                : $this->criarPaymentIntent($transacaoDTO);

            if ($paymentIntent->status === 'succeeded') {
                $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::APROVADO;
                $transacaoDTO->data_pagamento = now()->format('Y-m-d H:i:s');
                $this->transacaoRepository->updateTransacao($transacaoDTO);

                $saldoDTO = $this->_criandoSaldo($transacaoDTO);
                $this->saldoRepository->updateSaldo($saldoDTO, $transacaoDTO->tipo_transacao);

                return new ResponseDTO('sucesso', 'Compra realizada com sucesso');
            }

            $this->marcarRecusado($transacaoDTO);
            Log::warning("Pagamento não concluído");
            return new ResponseDTO('erro', 'Pagamento não concluído');

        } catch (\Stripe\Exception\CardException $e) {
            $this->marcarRecusado($transacaoDTO);
            Log::warning("Cartão recusado: {$e->getMessage()}");
            return new ResponseDTO('erro', 'Cartão recusado');

        } catch (\Stripe\Exception\RateLimitException|\Stripe\Exception\ApiConnectionException|\Stripe\Exception\ApiErrorException $e) {
            $this->reprocessarTransacao($transacaoDTO);
            Log::error("Erro temporário Stripe: {$e->getMessage()}");
            return new ResponseDTO('erro', 'Erro temporário, pode reprocessar');

        } catch (\Throwable $th) {
            Log::error("Erro inesperado: {$th->getMessage()} | {$th->getFile()} | linha: {$th->getLine()}");
            return new ResponseDTO('erro', 'Erro inesperado');
        }
    }

    private function criarPaymentIntent(TransacaoDTO $transacaoDTO): PaymentIntent
    {
        return PaymentIntent::create([
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
    }

    private function retriveAndConfirmPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

        // Confirma novamente apenas se estiver aguardando confirmação ou ação do usuário
        if (in_array($paymentIntent->status, ['requires_confirmation', 'requires_action'])) {
            $paymentIntent->confirm();
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId); // Atualiza o status
        }

        return $paymentIntent;
    }

    private function marcarRecusado(TransacaoDTO $transacaoDTO): void
    {
        $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
        $transacaoDTO->descricao_transacao = "Cartão recusado para {$transacaoDTO->nome} CPF: {$transacaoDTO->cpf}";
        $this->transacaoRepository->updateTransacao($transacaoDTO);
    }

    private function reprocessarTransacao(TransacaoDTO $transacaoDTO): void
    {
        $transacaoDTO->retentativa++;
        $body = $this->crypto->encrypt($transacaoDTO->__toString());
        $this->reprocessamentoAdapter->enfilerarReprocessamentoComprasCartao($body);
    }

    public function criptografarDadosCompraParaRealizarVenda(Request $request): string
    {
        return $this->crypto->encrypt(json_encode($request->all()));
    }

    public function realizarPostParaRotaComprarSaldoCartaoCredito(string $body)
    {
        $request = BodyRequest::create(
            route('compra.cartao.credito'),
            'POST',
            ['body' => $body]
        );

        return app()->call('App\Http\Controllers\TransacaoController@compra_cartao_credito', [
            'request' => $request
        ]);
    }

    private function _criandoTransacao(array $request, SituacaoTransacaoEnum $situacaoTransacaoEnum): TransacaoDTO
    {
        $objeto = json_decode($this->crypto->decrypt($request['body']));
        return new TransacaoDTO(
            $objeto->payment_method_id,
            $objeto->valor_compra,
            $situacaoTransacaoEnum,
            $objeto->descricao_transacao,
            TipoTransacaoEnum::from($objeto->tipo_transacao),
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
