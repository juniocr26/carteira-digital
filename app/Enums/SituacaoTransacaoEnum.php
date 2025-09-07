<?php

namespace App\Enums;

enum SituacaoTransacaoEnum: string {

    case APROVADO = '1';
    case PENDENTE_PAGAMENTO = '2';
    case RECUSADO = '3';
}
