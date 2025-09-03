<?php

namespace App\Enums;

enum TipoTransacaoEnum: string {

    case COMPRA_SALDO_CARTAO_CREDITO = '1';
    case COMPRA_CARTAO_CREDITO= '2';
    case COMPRA_USANDO_SALDO = '3';
}
