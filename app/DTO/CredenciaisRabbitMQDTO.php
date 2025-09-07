<?php

namespace App\DTO;

class CredenciaisRabbitMQDTO
{
    public function __construct(
        public string $host,
        public int $port,
        public string $username,
        public string $password,
        public string $vhost
    ) { }
}