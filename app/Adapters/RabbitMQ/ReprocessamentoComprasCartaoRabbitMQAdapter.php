<?php

namespace App\Adapters\RabbitMQ;
use App\Adapters\RabbitMQ\Interfaces\ReprocessamentoComprasCartaoRabbitMQAdapterInterface;
use App\DTO\ResponseDTO;
use App\Enums\RabbitMQEnum;
use Log;

class ReprocessamentoComprasCartaoRabbitMQAdapter extends RabbitMQAdapter implements ReprocessamentoComprasCartaoRabbitMQAdapterInterface
{
    protected string $exchange = 'exchange-reprocessamento-compras';
    protected string $queue = 'reprocessamento-compras-cartao';
    protected string $routingKey = 'key-reprocessamento-compras-cartao';

    public function __construct()
    {
        parent::__construct(RabbitMQEnum::REPROCESSAMENTO_COMPRAS_CARTAO->credenciais());
    }

    public function enfilerarReprocessamentoComprasCartao(string $body): ResponseDTO
    {
        try {
            $message = [
                'body' => $body
            ];

            $body = json_encode($message);
            $this->publishMessage($body);
            return new ResponseDTO('sucesso', 'Reprocessamento de compra feita no cartão enfileirado com sucesso');
        } catch (\Throwable $th) {
            Log::error("(Adapter) Erro ao enfilerar reprocessamento de compra feita no cartão - {$th->getMessage()} | file: {$th->getFile()} | linha: {$th->getLine()} | trace: {$th->getTraceAsString()}");
            return new ResponseDTO('erro',"(Adapter) Erro ao enfilerar reprocessamento de compra feita no cartão  - {$th->getMessage()}");
        }
    }
}
