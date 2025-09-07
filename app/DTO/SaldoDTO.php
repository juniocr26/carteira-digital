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
}
