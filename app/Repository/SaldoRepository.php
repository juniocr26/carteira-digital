<?php

namespace App\Repository;

use App\DTO\SaldoDTO;
use App\Models\Saldo;
use App\Repository\Interfaces\SaldoRepositoryInterface;
use App\Enums\TipoTransacaoEnum;

class SaldoRepository implements SaldoRepositoryInterface {

    public function __construct(
        private Saldo $saldoModel
    ) {}

    public function updateSaldo(SaldoDTO $saldoDTO, TipoTransacaoEnum $tipoTransacaoEnum): void
    {
        $saldo = $this->saldoModel::where('cpf', $saldoDTO->cpf)->first();

        if ($tipoTransacaoEnum->isDebito()) {
            $this->debitar($saldo, $saldoDTO);
        } else {
            $this->creditar($saldo, $saldoDTO);
        }
    }

    private function debitar(?Saldo $saldo, SaldoDTO $saldoDTO): void
    {
        if (!$saldo) {
            throw new \RuntimeException("Saldo inexistente para CPF {$saldoDTO->cpf}");
        }

        $saldo->update([
            'saldo'                   => $saldo->saldo - $saldoDTO->saldo,
            'data_ultima_atualizacao' => $saldoDTO->data_ultima_atualizacao,
        ]);
    }

    private function creditar(?Saldo $saldo, SaldoDTO $saldoDTO): void
    {
        if ($saldo) {
            $saldo->update([
                'saldo'                   => $saldo->saldo + $saldoDTO->saldo,
                'data_ultima_atualizacao' => $saldoDTO->data_ultima_atualizacao,
            ]);
        } else {
            $this->saldoModel::create([
                'cpf'                     => $saldoDTO->cpf,
                'saldo'                   => $saldoDTO->saldo,
                'data_ultima_atualizacao' => $saldoDTO->data_ultima_atualizacao,
            ]);
        }
    }
}
