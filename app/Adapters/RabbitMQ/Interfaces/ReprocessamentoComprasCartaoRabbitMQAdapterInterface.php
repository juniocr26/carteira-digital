<?php

namespace App\Adapters\RabbitMQ\Interfaces;
use App\DTO\ResponseDTO;

interface ReprocessamentoComprasCartaoRabbitMQAdapterInterface
{
    public function enfilerarReprocessamentoComprasCartao(string $body): ResponseDTO;
}
