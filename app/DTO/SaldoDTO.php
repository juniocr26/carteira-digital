<?php

namespace App\DTO;

use App\DTO\Base\DTOBase;

class SaldoDTO extends DTOBase
{
    public function __construct(
        public string $cpf,
        public float $saldo,
        public ?string $data_ultima_atualizacao = null,
    ) {}

    public function setDataUltimaAtualizacao(string $data_ultima_atualizacao): void
    {
        $this->data_ultima_atualizacao = $data_ultima_atualizacao;
    }

    public function getDataUltimaAtualizacao(): string
    {
        return $this->data_ultima_atualizacao;
    }
}
