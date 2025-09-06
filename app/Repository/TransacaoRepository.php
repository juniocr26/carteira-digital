<?php

namespace App\Repository;
use App\DTO\TransacaoDTO;
use App\Models\Transacao;
use App\Repository\Interfaces\TransacaoRepositoryInterface;

class TransacaoRepository implements TransacaoRepositoryInterface {

    public function __construct(
        private Transacao $transacaoModel
    ) {}

    public function updateTransacao(TransacaoDTO $transacaoDTO): void
    {
        $this->transacaoModel::updateOrCreate(
            [
                'cpf'               => $transacaoDTO->cpf,
                'payment_method_id' => $transacaoDTO->payment_method_id,
                'data_transacao'    => $transacaoDTO->data_transacao
            ],
            [
                'payment_method_id'     => $transacaoDTO->payment_method_id,
                'valor_compra'          => $transacaoDTO->valor_compra,
                'situacao_transacao'    => $transacaoDTO->situacao_transacao->value,
                'descricao_transacao'   => $transacaoDTO->descricao_transacao,
                'tipo_transacao'        => $transacaoDTO->tipo_transacao->value,
                'nome'                  => $transacaoDTO->nome,
                'cpf'                   => $transacaoDTO->cpf,
                'data_transacao'        => $transacaoDTO->data_transacao,
                'data_pagamento'        => $transacaoDTO->data_pagamento,
            ]
        );
    }
}
