<?php

namespace App\Repository;

use App\DTO\StatusTransacoesPendentesDTO;
use App\Enums\SituacaoTransacaoEnum;
use App\Models\StatusTransacoesPendentes;
use App\Repository\Interfaces\StatusTransacoesPendentesRepositoryInterface;

class StatusTransacoesPendentesRepository implements StatusTransacoesPendentesRepositoryInterface {

    public function __construct(
        private StatusTransacoesPendentes $statusTransacoesPendentesModel
    ) {}

    public function updateStatusTransacoesPendentes(StatusTransacoesPendentesDTO $statusTransacoesPendentesDTO): void
    {
        $this->statusTransacoesPendentesModel::updateOrCreate(
            [
                'payment_method_id'             => $statusTransacoesPendentesDTO->payment_method_id,
                'data_ultima_atualizacao'       => $statusTransacoesPendentesDTO->data_ultima_atualizacao
            ],
            [
                'payment_method_id'         => $statusTransacoesPendentesDTO->payment_method_id,
                'situacao_transacao'        => $statusTransacoesPendentesDTO->situacao_transacao->value,
                'data_ultima_atualizacao'   => $statusTransacoesPendentesDTO->data_ultima_atualizacao,
            ]
        );
    }

    public function findTransaction(string $transaction_id): StatusTransacoesPendentesDTO|null
    {
        $statusTransacoesPendentesDTO = $this->statusTransacoesPendentesModel::select([
            'payment_method_id',
            'situacao_transacao',
            'data_ultima_atualizacao'
        ])
        ->where("payment_method_id", $transaction_id)
        ->first();

        if ($statusTransacoesPendentesDTO) {
            return $this->_criandoStatusTransacoesPendentes(
                $statusTransacoesPendentesDTO->payment_method_id,
                SituacaoTransacaoEnum::from($statusTransacoesPendentesDTO->situacao_transacao),
                $statusTransacoesPendentesDTO->data_ultima_atualizacao
            );
        }

        return null;
    }


    private function _criandoStatusTransacoesPendentes($payment_method_id, $situacao_transacao): StatusTransacoesPendentesDTO
    {
        return new StatusTransacoesPendentesDTO(
            $payment_method_id,
            $situacao_transacao,
            now()->format('Y-m-d H:i:s')
        );
    }
}
