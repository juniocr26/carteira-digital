<?php

namespace App\Enums;

enum TipoTransacaoEnum: string {

    case COMPRA_SALDO_CARTAO_CREDITO    = '1';
    case COMPRA_CARTAO_CREDITO          = '2';
    case COMPRA_USANDO_SALDO            = '3';
    case ESTORNO                        = '4';

    public function isDebito(): bool
    {
        return match($this) {
            self::COMPRA_SALDO_CARTAO_CREDITO,
            self:: ESTORNO => false,
            self::COMPRA_USANDO_SALDO => true,
            default => true,
        };
    }

    public function isCredito(): bool
    {
        return ! $this->isDebito();
    }
}
