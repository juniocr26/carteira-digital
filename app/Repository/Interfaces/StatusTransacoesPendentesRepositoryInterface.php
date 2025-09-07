<?php

namespace App\Repository\Interfaces;

use App\DTO\StatusTransacoesPendentesDTO;

interface StatusTransacoesPendentesRepositoryInterface
{
    public function updateStatusTransacoesPendentes(StatusTransacoesPendentesDTO $statusTransacoesPendentesDTO): void;
    public function findTransaction(string $transaction_id): StatusTransacoesPendentesDTO|null;
}
