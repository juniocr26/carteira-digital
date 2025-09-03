<?php

namespace App\DTO;

use App\DTO\Base\DTOBase;
use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;

class TransacaoDTO extends DTOBase
{
    public function __construct(
        public string $payment_method_id,
        public float $valor_compra,
        public SituacaoTransacaoEnum $situacao_transacao,
        public string $descricao_transacao,
        public TipoTransacaoEnum $tipo_transacao,
        public string $nome,
        public string $cpf,
        public ?string $data_transacao = null,
        public ?string $data_pagamento = null,
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
