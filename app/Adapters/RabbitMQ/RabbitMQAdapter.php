<?php

namespace App\Adapters\RabbitMQ;

use App\Adapters\RabbitMQ\Interfaces\RabbitMQAdapterInterface;
use App\DTO\CredenciaisRabbitMQDTO;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

abstract class RabbitMQAdapter implements RabbitMQAdapterInterface
{
    protected AMQPStreamConnection $connection;
    protected string $exchange;
    protected string $queue;
    protected string $routingKey;

    public function __construct(CredenciaisRabbitMQDTO $credenciais)
    {
        $this->connection = new AMQPStreamConnection(
            $credenciais->host,
            $credenciais->port,
            $credenciais->username,
            $credenciais->password,
            $credenciais->vhost
        );
    }

    public function publishMessage(string $body): void
    {
        $channel = $this->getChannel();

        $this->declareExchange();
        $this->declareQueue();
        $this->bindRoutes();

        $message = new AMQPMessage($body);

        $channel->basic_publish(
            $message,
            $this->exchange,
            $this->routingKey
        );

        $this->closeConnection();
    }

    public function listenQueue(\Closure $callback): void
    {
        $channel = $this->getChannel();

        $this->declareQueue();

        $channel->basic_consume(
            queue: $this->queue,
            callback: $callback
        );

        while ($channel->is_open()) {
            $channel->wait(timeout: 3600);
        }

        $this->closeConnection();
    }

    protected function getChannel(): AMQPChannel
    {
        return $this->connection->channel();
    }

    private function declareExchange(): void
    {
        $this->getChannel()->exchange_declare(
            exchange: $this->exchange,
            type: AMQPExchangeType::TOPIC,
            auto_delete: false,
            durable: true
        );
    }

    protected function declareQueue(): void
    {
        $this->getChannel()->queue_declare(
            queue: $this->queue,
            auto_delete: false,
            durable: true
        );
    }

    private function bindRoutes(): void
    {
        $this->getChannel()->queue_bind(
            exchange: $this->exchange,
            queue: $this->queue,
            routing_key: $this->routingKey
        );
    }

    protected function closeConnection(): void
    {
        $this->connection->channel()->close();
        $this->connection->close();
    }
}
