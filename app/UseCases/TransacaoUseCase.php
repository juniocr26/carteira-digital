<?php

namespace App\UseCases;

use Stripe\Stripe;
use App\DTO\SaldoDTO;
use CriptoLib\Crypto;
use App\DTO\ResponseDTO;
use App\DTO\TransacaoDTO;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use App\Enums\TipoTransacaoEnum;
use App\Http\Requests\BodyRequest;
use Illuminate\Support\Facades\Log;
use App\Enums\SituacaoTransacaoEnum;
use App\DTO\StatusTransacoesPendentesDTO;
use App\Repository\Interfaces\SaldoRepositoryInterface;
use App\Repository\Interfaces\TransacaoRepositoryInterface;
use App\Repository\Interfaces\StatusTransacoesPendentesRepositoryInterface;
use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;

class TransacaoUseCase
{
    private const MAX_RETENTATIVAS = 3;

    public function __construct(
        private TransacaoRepositoryInterface $transacaoRepository,
        private SaldoRepositoryInterface $saldoRepository,
        private ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoAdapter,
        private StatusTransacoesPendentesRepositoryInterface $statusTransacoesPendentesRepository,
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

    public function realizarCompraPix(array $request): ResponseDTO
    {
        try {
            // Cria transação em estado "pendente"
            $transacaoDTO = $this->_criandoTransacao($request,SituacaoTransacaoEnum::PENDENTE_PAGAMENTO);
            $this->transacaoRepository->updateTransacao($transacaoDTO);
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $pi = PaymentIntent::create([
                'amount' => intval($transacaoDTO->valor_compra * 100), // sempre em centavos
                'currency' => 'brl',
                'payment_method_types' => ['pix'],
                'payment_method_options' => [
                    'pix' => [
                        'expires_after_seconds' => 3600,
                    ],
                ],
                'description' => $transacaoDTO->descricao_transacao,
                'metadata' => [
                    'id_transacao' => "$transacaoDTO->descricao_transacao as $transacaoDTO->data_transacao",
                    'cpf' => $transacaoDTO->cpf,
                    'nome' => $transacaoDTO->nome,
                ]
            ]);

            return new ResponseDTO(
                'sucesso',
                'Pix gerado com sucesso',
                [
                    'client_secret' => $pi->client_secret,
                    'payment_intent' => $pi
                ]
            );

        } catch (\Stripe\Exception\ApiErrorException $e) {
            $error = $e->getError();
            $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
            $this->transacaoRepository->updateTransacao($transacaoDTO);

            // mapeando códigos comuns para Pix
            switch ($error->code ?? '') {
                case 'parameter_invalid_integer':
                    return new ResponseDTO('erro', 'Valor da transação inválido. Informe em centavos.');
                case 'invalid_request_error':
                    return new ResponseDTO('erro', 'Requisição inválida. Verifique parâmetros do Pix.');
                case 'resource_missing':
                    return new ResponseDTO('erro', 'Transação Pix não encontrada.');
                case 'payment_intent_unexpected_state':
                    return new ResponseDTO('erro', 'Este Pix já foi processado ou expirou.');
                default:
                    return new ResponseDTO('erro', 'Erro Pix: ' . $error->message);
            }

        } catch (\Exception $e) {
            return new ResponseDTO('erro', 'Erro interno: ' . $e->getMessage());
        }
    }

    private function processarPagamento(TransacaoDTO $transacaoDTO, bool $isRetentativa): ResponseDTO
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentIntent = $isRetentativa && $transacaoDTO->payment_method_id
                ? $this->retriveAndConfirmPaymentIntent($transacaoDTO)
                : $this->criarPaymentIntent($transacaoDTO);

            if ($paymentIntent->status === 'succeeded') {
                $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::APROVADO;
                $transacaoDTO->data_pagamento = now()->format('Y-m-d H:i:s');
                $this->transacaoRepository->updateTransacao($transacaoDTO);

                $saldoDTO = $this->_criandoSaldo($transacaoDTO);
                $this->saldoRepository->updateSaldo($saldoDTO, $transacaoDTO->tipo_transacao);

                if($transacaoDTO->retentativa > 0) {
                    $statusTransacoesPendentesDTO = $this->_criandoStatusTransacoesPendentes($transacaoDTO->payment_method_id, $transacaoDTO->situacao_transacao);
                    $this->statusTransacoesPendentesRepository->updateStatusTransacoesPendentes($statusTransacoesPendentesDTO);
                }
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
            $transacaoDTO->payment_intent_is_null = is_null($paymentIntent);
            $this->reprocessarTransacao($transacaoDTO);
            Log::warning("Erro temporário Stripe: {$e->getMessage()}");
            return new ResponseDTO('warning', 'Erro temporário, pode reprocessar', $transacaoDTO->payment_method_id);

        } catch (\Throwable $th) {
            $this->marcarRecusado($transacaoDTO);
            Log::error("Erro inesperado: {$th->getMessage()} | {$th->getFile()} | linha: {$th->getLine()}");
            return new ResponseDTO('erro', 'Erro inesperado');
        }
    }

    private function criarPaymentIntent(TransacaoDTO $transacaoDTO)
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

    private function retriveAndConfirmPaymentIntent(TransacaoDTO $transacaoDTO): PaymentIntent
    {
        if(!$transacaoDTO->payment_intent_is_null){
            $paymentIntent = PaymentIntent::retrieve($transacaoDTO->payment_method_id);

            if (in_array($paymentIntent->status, ['requires_confirmation', 'requires_action'])) {
                $paymentIntent = $paymentIntent->confirm(); // já retorna atualizado
            }
        }else{
            $paymentIntent = $this->criarPaymentIntent($transacaoDTO);
        }

        return $paymentIntent;
    }

    private function marcarRecusado(TransacaoDTO $transacaoDTO): void
    {
        $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
        $transacaoDTO->descricao_transacao = "Cartão recusado para {$transacaoDTO->nome} CPF: {$transacaoDTO->cpf}";
        $this->transacaoRepository->updateTransacao($transacaoDTO);
        if($transacaoDTO->retentativa > 0){
            $statusTransacoesPendentesDTO = $this->_criandoStatusTransacoesPendentes($transacaoDTO->payment_method_id, $transacaoDTO->situacao_transacao);
            $this->statusTransacoesPendentesRepository->updateStatusTransacoesPendentes($statusTransacoesPendentesDTO);
        }
    }

    private function reprocessarTransacao(TransacaoDTO $transacaoDTO): void
    {
        if ($transacaoDTO->retentativa >= self::MAX_RETENTATIVAS) {
            $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
            $this->transacaoRepository->updateTransacao($transacaoDTO);
            $statusTransacoesPendentesDTO = $this->_criandoStatusTransacoesPendentes($transacaoDTO->payment_method_id, $transacaoDTO->situacao_transacao);
            $this->statusTransacoesPendentesRepository->updateStatusTransacoesPendentes($statusTransacoesPendentesDTO);
            return;
        }

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

    public function realizarPostParaRotaComprarPix(string $body)
    {
        $request = BodyRequest::create(
            route('compra.pix'),
            'POST',
            ['body' => $body]
        );

        return app()->call('App\Http\Controllers\TransacaoController@compra_pix', [
            'request' => $request
        ]);
    }

    private function _criandoTransacao(array $request, SituacaoTransacaoEnum $situacaoTransacaoEnum): TransacaoDTO
    {
        $objeto = json_decode($this->crypto->decrypt($request['body']));
        $data_transacao = now()->format('Y-m-d H:i:s');
        return new TransacaoDTO(
            $objeto->payment_method_id ?? "Compra pix realizada para o CPF: {$objeto->cpf}, as {$data_transacao}",
            $objeto->valor_compra,
            $situacaoTransacaoEnum,
            $objeto->descricao_transacao,
            TipoTransacaoEnum::from($objeto->tipo_transacao),
            $objeto->nome,
            $objeto->cpf,
            $data_transacao
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

    private function _criandoStatusTransacoesPendentes($payment_method_id, $situacao_transacao): StatusTransacoesPendentesDTO
    {
        return new StatusTransacoesPendentesDTO(
            $payment_method_id,
            $situacao_transacao,
            now()->format('Y-m-d H:i:s')
        );
    }
}
