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
        private TransacaoRepositoryInterface $transacaoRepository,
        private SaldoRepositoryInterface $saldoRepository,
        private ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoComprasCartaoRabbitMQAdapter,
        private Crypto $crypto
    ) {}

    public function retentativaPagamento(TransacaoDTO $transacaoDTO): ResponseDTO
    {
        return $this->processarPagamentoStripe($transacaoDTO);
    }

    public function realizaCompraCartaoCredito(array $request): ResponseDTO
    {
        $transacaoDTO = $this->criarTransacao($request, SituacaoTransacaoEnum::PENDENTE_PAGAMENTO);
        return $this->processarPagamentoStripe($transacaoDTO);
    }

    private function processarPagamentoStripe(TransacaoDTO $transacaoDTO): ResponseDTO
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
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
                $this->aprovarTransacao($transacaoDTO);
                return new ResponseDTO('sucesso', 'Compra realizada com sucesso');
            }

            return new ResponseDTO('erro', 'Pagamento não concluído');

        } catch (\Stripe\Exception\CardException $e) {
            $this->recusarTransacao($transacaoDTO, $e->getMessage());
            return new ResponseDTO('erro', 'Cartão recusado');

        } catch (\Stripe\Exception\RateLimitException|
                 \Stripe\Exception\ApiConnectionException|
                 \Stripe\Exception\ApiErrorException $e) {
            $this->reprocessarTransacao($transacaoDTO, $e->getMessage());
            return new ResponseDTO('erro', 'Erro temporário, pode reprocessar');

        } catch (\Throwable $th) {
            Log::error("Erro inesperado: {$th->getMessage()} | {$th->getFile()} | linha: {$th->getLine()}");
            return new ResponseDTO('erro', 'Erro inesperado');
        }
    }

    private function aprovarTransacao(TransacaoDTO $transacaoDTO): void
    {
        $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::APROVADO;
        $transacaoDTO->data_pagamento = now()->format('Y-m-d H:i:s');
        $this->transacaoRepository->updateTransacao($transacaoDTO);

        $saldoDTO = $this->criarSaldo($transacaoDTO);
        $this->saldoRepository->updateSaldo($saldoDTO, $transacaoDTO->tipo_transacao);
    }

    private function recusarTransacao(TransacaoDTO $transacaoDTO, string $motivo): void
    {
        $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
        $transacaoDTO->descricao_transacao = "Cartão recusado para a compra de saldo do cliente {$transacaoDTO->nome} CPF: {$transacaoDTO->cpf}";
        $this->transacaoRepository->updateTransacao($transacaoDTO);
        Log::warning("Cartão recusado: {$motivo}");
    }

    private function reprocessarTransacao(TransacaoDTO $transacaoDTO, string $erro): void
    {
        $transacaoDTO->retentativa++;
        $body = $this->crypto->encrypt($transacaoDTO->__toString());
        $this->reprocessamentoComprasCartaoRabbitMQAdapter->enfilerarReprocessamentoComprasCartao($body);
        Log::error("Erro temporário Stripe: {$erro}");
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

    private function criarTransacao(array $request, SituacaoTransacaoEnum $situacaoTransacaoEnum): TransacaoDTO
    {
        $transacaoData = json_decode($this->crypto->decrypt($request['body']));
        return new TransacaoDTO(
            $transacaoData->payment_method_id,
            $transacaoData->valor_compra,
            $situacaoTransacaoEnum,
            $transacaoData->descricao_transacao,
            TipoTransacaoEnum::from($transacaoData->tipo_transacao),
            $transacaoData->nome,
            $transacaoData->cpf,
            now()->format('Y-m-d H:i:s')
        );
    }

    private function criarSaldo(TransacaoDTO $transacaoDTO): SaldoDTO
    {
        return new SaldoDTO(
            $transacaoDTO->cpf,
            $transacaoDTO->valor_compra,
            now()->format('Y-m-d H:i:s')
        );
    }
}
