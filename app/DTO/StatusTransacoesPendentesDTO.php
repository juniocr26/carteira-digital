<?php

namespace App\DTO;

use App\DTO\Base\DTOBase;
use App\Enums\SituacaoTransacaoEnum;
use App\Enums\TipoTransacaoEnum;

class StatusTransacoesPendentesDTO extends DTOBase
{
    public function __construct(
        public string $payment_method_id,
        public SituacaoTransacaoEnum $situacao_transacao,
        public ?string $data_ultima_atualizacao = null,
    ) {}

    public function __toString(): string
    {
        return json_encode([
            'payment_method_id'         => $this->payment_method_id,
            'situacao_transacao'        => $this->situacao_transacao->value,
            'data_ultima_atualizacao'   => $this->data_ultima_atualizacao,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
