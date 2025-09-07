<?php

namespace App\Enums;
use App\DTO\CredenciaisRabbitMQDTO;

enum RabbitMQEnum: string
{
    case REPROCESSAMENTO_COMPRAS_CARTAO = 'reprocessamento_compras_cartao';

    public function credenciais(): CredenciaisRabbitMQDTO
    {
        return match ($this) {
            RabbitMQEnum::REPROCESSAMENTO_COMPRAS_CARTAO => new CredenciaisRabbitMQDTO(
                config('services.rabbitmq.reprocessamento_compras.host'),
                config('services.rabbitmq.reprocessamento_compras.port'),
                config('services.rabbitmq.reprocessamento_compras.username'),
                config('services.rabbitmq.reprocessamento_compras.password'),
                config('services.rabbitmq.reprocessamento_compras.vhost')
            ),

            default => new CredenciaisRabbitMQDTO(
                config('services.rabbitmq.reprocessamento_compras.host'),
                config('services.rabbitmq.reprocessamento_compras.port'),
                config('services.rabbitmq.reprocessamento_compras.username'),
                config('services.rabbitmq.reprocessamento_compras.password'),
                config('services.rabbitmq.reprocessamento_compras.vhost')
            )
        };
    }
}
