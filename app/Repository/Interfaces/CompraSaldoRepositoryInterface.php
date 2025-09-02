<?php

namespace App\Repository\Interfaces;

use App\DTO\CompraSaldoDTO;

interface CompraSaldoRepositoryInterface
{
    public function updateCompraSaldo(CompraSaldoDTO $compraSaldo): void;
}
