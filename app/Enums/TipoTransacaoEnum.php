<?php

namespace App\Enums;

enum TipoTransacaoEnum: string {

    case COMPRA_SALDO_CARTAO_CREDITO    = '1';
    case COMPRA_CARTAO_CREDITO          = '2';
    case COMPRA_USANDO_SALDO            = '3';
    case DEBITO                         = '4';

    public function isDebito(): bool
    {
        return match($this) {
            self::COMPRA_SALDO_CARTAO_CREDITO => false,
            self::COMPRA_USANDO_SALDO,
            self::DEBITO => true,
            default => true,
        };
    }

    public function isCredito(): bool
    {
        return ! $this->isDebito();
    }
}
