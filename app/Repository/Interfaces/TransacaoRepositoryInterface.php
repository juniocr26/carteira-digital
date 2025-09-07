<?php

namespace App\Repository\Interfaces;

use App\DTO\TransacaoDTO;

interface TransacaoRepositoryInterface
{
    public function updateTransacao(TransacaoDTO $transacaoDTO): void;
}
