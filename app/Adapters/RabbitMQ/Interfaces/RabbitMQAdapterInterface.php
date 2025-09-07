<?php

namespace App\Adapters\RabbitMQ\Interfaces;

interface RabbitMQAdapterInterface
{
    public function publishMessage(string $body): void;
    public function listenQueue(\Closure $callback): void;
}
