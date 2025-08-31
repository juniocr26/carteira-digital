<?php

namespace App\Repository;
use App\DTO\ResponseDTO;
use App\DTO\CompraSaldoDTO;
use App\Models\CompraSaldo;
use Illuminate\Support\Facades\Log;
use App\Repository\Interfaces\CompraSaldoRepositoryInterface;

class CompraSaldoRepository implements CompraSaldoRepositoryInterface {

    public function __construct(
        private CompraSaldo $compraSaldoModel = new CompraSaldo()
    ) {}

    public function updateCompraSaldo(CompraSaldoDTO $compraSaldo): ResponseDTO
    {
        try {
            $this->compraSaldoModel::updateOrCreate(
                [
                    'cpf' => $compraSaldo->cpf,
                    'oid_cartao' => $compraSaldo->oid_cartao
                ],
                [
                    'cartao_numero' => $compraSaldo->cartao_numero,
                    'cartao_cvv' => $compraSaldo->cartao_cvv,
                    'cartao_mes' => $compraSaldo->cartao_mes,
                    'cartao_ano' => $compraSaldo->cartao_ano,
                    'valor_compra' => $compraSaldo->valor_compra,
                    'data_transacao' => $compraSaldo->data_transacao,
                    'data_pagamento' => $compraSaldo->data_pagamento,
                    'situacao_transacao' => $compraSaldo->situacao_transacao->value,
                ]
            );

            return new ResponseDTO('sucesso', 'Compra com saldo atualizada com sucesso', $compraSaldo);
        } catch (\Throwable $th) {
            Log::error("Não foi possível criar a transação de compra de saldo, {$th->getMessage()} | file: {$th->getFile()} | linha: {$th->getLine()} | trace: {$th->getTraceAsString()}");
            return new ResponseDTO('erro', "Não foi possível criar a transação de compra de saldo");
        }
    }


    /**
     * =================================================================
     *  Helpers
     * =================================================================
     */

    private function _formatarCompraSaldoParaDTO(array $compraSaldo): CompraSaldoDTO {
        return new CompraSaldoDTO(
            $compraSaldo['cartao_numero'],
            $compraSaldo['cartao_cvv'],
            $compraSaldo['cartao_mes'],
            $compraSaldo['cartao_ano'],
            $compraSaldo['oid_cartao'],
            $compraSaldo['cpf'],
            $compraSaldo['valor_compra'],
            $compraSaldo['situacao_transacao']
        );
    }
}
