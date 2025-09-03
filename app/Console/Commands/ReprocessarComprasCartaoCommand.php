<?php

namespace App\Console\Commands;

use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;
use App\DTO\TransacaoDTO;
use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;
use App\UseCases\TransacaoUseCase;
use CriptoLib\Crypto;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class ReprocessarComprasCartaoCommand extends Command
{
    protected $signature = 'amqp-consumer:reprocessar-compras-cartao-credito';
    protected $description = 'Lê a fila de reprocessamento de compras feitas com cartão de crédito';

    protected ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoComprasCartaoAdapter;
    protected Crypto $crypto;
    protected TransacaoUseCase $transacaoUseCase;

    public function __construct(
        ReprocessamentoComprasCartaoRabbitMQAdapterInterface $reprocessamentoComprasCartaoAdapter,
        Crypto $crypto,
        TransacaoUseCase $transacaoUseCase
    ) {
        $this->reprocessamentoComprasCartaoAdapter = $reprocessamentoComprasCartaoAdapter;
        $this->crypto = $crypto;
        $this->transacaoUseCase = $transacaoUseCase;

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

        // Verifica limite de retentativas
        if ($transacaoData->retentativa > 3) {
            $channel->basic_reject($deliveryTag, false);
            $this->info("Mensagem rejeitada após 3 tentativas: {$transacaoData->descricao_transacao}");
            return;
        }

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

        $transacaoDTO->retentativa = $transacaoData->retentativa;

        // Executa retentativa via UseCase
        $this->transacaoUseCase->retentativaPagamento($transacaoDTO);

        // Confirma a mensagem
        $channel->basic_ack($deliveryTag);
        $this->info("Mensagem processada com sucesso: {$transacaoDTO->descricao_transacao}");
    }
}
