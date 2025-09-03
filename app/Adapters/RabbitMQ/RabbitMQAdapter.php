<?php

namespace App\Adapters\RabbitMQ;

use App\Adapters\RabbitMQ\Interfaces\RabbitMQAdapterInterface;
use App\DTO\CredenciaisRabbitMQDTO;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class RabbitMQAdapter implements RabbitMQAdapterInterface
{
    protected AMQPStreamConnection $connection;
    protected ?AMQPChannel $channel = null;
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

        $message = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($message, $this->exchange, $this->routingKey);

        $this->closeConnection();
    }

    /**
     * Escuta a fila e processa as mensagens.
     * O callback recebe apenas o AMQPMessage; channel e delivery_tag
     * devem ser obtidos via $message->delivery_info dentro do callback.
     */
    public function listenQueue(\Closure $callback): void
    {
        $channel = $this->getChannel();

        $this->declareQueue();
        $this->bindRoutes();

        $channel->basic_consume(
            queue: $this->queue,
            callback: function (AMQPMessage $message) use ($callback) {
                $callback($message);
            }
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $this->closeConnection();
    }

    protected function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    protected function declareExchange(): void
    {
        $this->getChannel()->exchange_declare(
            exchange: $this->exchange,
            type: 'topic', // Pode ser 'direct', 'fanout', 'headers', 'topic'
            passive: false,
            durable: true,
            auto_delete: false
        );
    }

    protected function declareQueue(): void
    {
        $this->getChannel()->queue_declare(
            queue: $this->queue,
            passive: false,
            durable: true,
            exclusive: false,
            auto_delete: false
        );
    }

    protected function bindRoutes(): void
    {
        $this->getChannel()->queue_bind(
            queue: $this->queue,
            exchange: $this->exchange,
            routing_key: $this->routingKey
        );
    }

    protected function closeConnection(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }
        $this->connection->close();
    }
}
