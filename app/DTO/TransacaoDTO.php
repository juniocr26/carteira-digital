<?php

namespace App\DTO;

use App\DTO\Base\DTOBase;
use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;

class TransacaoDTO extends DTOBase
{
    public int $retentativa = 0;
    public bool $payment_intent_is_null = false;

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

    public function __toString(): string
    {
        return json_encode([
            'payment_method_id'         => $this->payment_method_id,
            'valor_compra'              => $this->valor_compra,
            'situacao_transacao'        => $this->situacao_transacao->value,
            'descricao_transacao'       => $this->descricao_transacao,
            'tipo_transacao'            => $this->tipo_transacao->value,
            'nome'                      => $this->nome,
            'cpf'                       => $this->cpf,
            'data_transacao'            => $this->data_transacao,
            'data_pagamento'            => $this->data_pagamento,
            'retentativa'               => $this->retentativa,
            'payment_intent_is_null'    => $this->payment_intent_is_null
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
