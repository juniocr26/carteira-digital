<?php

namespace App\DTO;

use App\DTO\Base\DTOBase;
use App\Enums\SituacaoTransacaoEnum;

class CompraSaldoDTO extends DTOBase
{
    public function __construct(
        public string $cartao_numero,
        public string $cartao_cvv,
        public string $cartao_mes,
        public string $cartao_ano,
        public string $oid_cartao,
        public string $cpf,
        public float $valor_compra,
        public SituacaoTransacaoEnum $situacao_transacao,
        public string $data_transacao = '',
        public string $data_pagamento = '',
    ) {}

    public function setDataTransacao(string $data_transacao): void
    {
        $this->data_transacao = $data_transacao;
    }

    public function getDataTransacao(): string
    {
        return $this->data_transacao;
    }

    public function setDataPagamento(string $data_pagamento): void
    {
        $this->data_pagamento = $data_pagamento;
    }

    public function getDataPagamento(): string
    {
        return $this->data_pagamento;
    }
}
