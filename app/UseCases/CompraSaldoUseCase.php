<?php

namespace App\UseCases;

use App\DTO\CompraSaldoDTO;
use App\Enums\SituacaoTransacaoEnum;
use CriptoLib\Crypto;
use App\DTO\ResponseDTO;
use App\Repository\CompraSaldoRepository;
use App\Repository\Interfaces\CompraSaldoRepositoryInterface;

class CompraSaldoUseCase
{
    public function __construct(
        private CompraSaldoRepositoryInterface $compraSaldoRepository = new CompraSaldoRepository(),
        private $crypto = new Crypto()
    ) {}

    public function realizaCompraSaldoCartaoCredito(array $request): ResponseDTO
    {
        $compraSaldo = $this->_criandoCompraSaldoCredito($request);
        return $this->compraSaldoRepository->updateCompraSaldo($compraSaldo);
    }

    private function _criandoCompraSaldoCredito(array $request): CompraSaldoDTO
    {
        return new CompraSaldoDTO(
            $this->crypto->decrypt($request['cartaoNumero']),
            $this->crypto->decrypt($request['cartaoCvv']),
            $this->crypto->decrypt($request['cartaoMes']),
            $this->crypto->decrypt($request['cartaoAno']),
            $request['oidCartao'],
            $request['cpf'],
            $request['valorCompra'],
            SituacaoTransacaoEnum::PENDENTE_PAGAMENTO,
            now()->format('Y-m-d H:i:s')
        );
    }
}
