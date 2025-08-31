<?php

namespace App\Repository\Interfaces;

use App\DTO\ResponseDTO;
use App\DTO\CompraSaldoDTO;

interface CompraSaldoRepositoryInterface
{
    public function updateCompraSaldo(CompraSaldoDTO $compraSaldo): ResponseDTO;
}
