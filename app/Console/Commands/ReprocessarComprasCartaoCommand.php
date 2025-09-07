<?php

namespace App\Console\Commands;

use CriptoLib\Crypto;
use App\DTO\TransacaoDTO;
use Illuminate\Console\Command;
use App\Enums\TipoTransacaoEnum;
use App\UseCases\TransacaoUseCase;
use PhpAmqpLib\Message\AMQPMessage;
use App\Enums\SituacaoTransacaoEnum;
use App\Repository\Interfaces\TransacaoRepositoryInterface;
use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;

class ReprocessarComprasCartaoCommand extends Command
{
    protected $signature = 'amqp-consumer:reprocessar-compras-cartao-credito';
    protected $description = 'Lê a fila de reprocessamento de compras feitas com cartão de crédito';

    protected ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoComprasCartaoAdapter;
    protected Crypto $crypto;
    protected TransacaoUseCase $transacaoUseCase;
    protected TransacaoRepositoryInterface $transacaoRepository;

    public function __construct(
        ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoComprasCartaoAdapter,
        Crypto $crypto,
        TransacaoUseCase $transacaoUseCase,
        TransacaoRepositoryInterface $transacaoRepository
    ) {
        $this->reprocessamentoComprasCartaoAdapter = $reprocessamentoComprasCartaoAdapter;
        $this->crypto = $crypto;
        $this->transacaoUseCase = $transacaoUseCase;
        $this->transacaoRepository = $transacaoRepository;

        parent::__construct();
    }

    public function handle(): int
    {
        $this->info("Executando {$this->signature}");

        $this->reprocessamentoComprasCartaoAdapter->listenQueue(function (AMQPMessage $message) {
            $this->onMessage($message);
        });

        return Command::SUCCESS;
    }

    private function onMessage(AMQPMessage $messageAMQP): void
    {
        // Recupera o channel e delivery tag do AMQPMessage
        $channel = $messageAMQP->delivery_info['channel'];
        $deliveryTag = $messageAMQP->delivery_info['delivery_tag'];

        // Decodifica a mensagem
        $messageBody = json_decode($messageAMQP->getBody());
        $transacaoData = $this->crypto->decrypt($messageBody->body);
        $transacaoData = json_decode($transacaoData);

        \Log::warning("O Pagamento está em retentativa {$transacaoData->retentativa}");

        // Cria DTO
        $transacaoDTO = new TransacaoDTO(
            $transacaoData->payment_method_id,
            $transacaoData->valor_compra,
            SituacaoTransacaoEnum::from($transacaoData->situacao_transacao),
            $transacaoData->descricao_transacao,
            TipoTransacaoEnum::from($transacaoData->tipo_transacao),
            $transacaoData->nome,
            $transacaoData->cpf,
            $transacaoData->data_transacao ?? null,
            $transacaoData->data_pagamento ?? null
        );
        $transacaoDTO->payment_intent_is_null = $transacaoData->payment_intent_is_null;

        // Verifica limite de retentativas
        if ($transacaoData->retentativa > 3) {
            $transacaoDTO->situacao_transacao = SituacaoTransacaoEnum::RECUSADO;
            $this->transacaoRepository->updateTransacao($transacaoDTO);
            $this->info("Mensagem rejeitada após 3 tentativas: {$transacaoData->descricao_transacao}");
            $channel->basic_reject($deliveryTag, false);
            return;
        }

        $transacaoDTO->retentativa = $transacaoData->retentativa;
        $this->info("Mensagem processada com sucesso: {$transacaoDTO->descricao_transacao}");

        // Executa retentativa via UseCase
        $this->transacaoUseCase->retentativaPagamento($transacaoDTO);

        // Confirma a mensagem
        $channel->basic_ack($deliveryTag);
    }
}
