<?php

namespace App\Repository\Interfaces;

use App\DTO\SaldoDTO;
use App\Enums\TipoTransacaoEnum;

interface SaldoRepositoryInterface
{
    public function updateSaldo(SaldoDTO $saldoDTO, TipoTransacaoEnum $tipoTransacaoEnum): void;
}
