<?php

namespace App\Repository;
use App\DTO\ResponseDTO;
use App\DTO\CompraSaldoDTO;
use App\Models\CompraSaldo;
use App\Repository\Interfaces\CompraSaldoRepositoryInterface;

class CompraSaldoRepository implements CompraSaldoRepositoryInterface {

    public function __construct(
        private CompraSaldo $compraSaldoModel = new CompraSaldo()
    ) {}

    public function updateCompraSaldo(CompraSaldoDTO $compraSaldo): void
    {
        $this->compraSaldoModel::updateOrCreate(
            [
                'cpf' => $compraSaldo->cpf,
            ],
            [
                'payment_method_id'     => $compraSaldo->payment_method_id,
                'zip_code'              => $compraSaldo->zip_code,
                'valor_compra'          => $compraSaldo->valor_compra,
                'situacao_transacao'    => $compraSaldo->situacao_transacao->value,
                'nome'                  => $compraSaldo->nome,
                'cpf'                   => $compraSaldo->cpf,
                'data_transacao'        => $compraSaldo->data_transacao,
                'data_pagamento'        => $compraSaldo->data_pagamento,
            ]
        );
    }


    /**
     * =================================================================
     *  Helpers
     * =================================================================
     */

    private function _formatarCompraSaldoParaDTO(array $compraSaldo): CompraSaldoDTO {
        return new CompraSaldoDTO(
            $compraSaldo['payment_method_id'],
            $compraSaldo['zip_code'],
            $compraSaldo['valor_compra'],
            $compraSaldo['situacao_transacao'],
            $compraSaldo['nome'],
            $compraSaldo['cpf']
        );
    }
}
