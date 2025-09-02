<?php

namespace App\UseCases;

use App\DTO\CompraSaldoDTO;
use App\Enums\SituacaoTransacaoEnum;
use CriptoLib\Crypto;
use App\DTO\ResponseDTO;
use App\Repository\CompraSaldoRepository;
use App\Repository\Interfaces\CompraSaldoRepositoryInterface;
use Stripe\Stripe;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class CompraSaldoUseCase
{
    public function __construct(
        private CompraSaldoRepositoryInterface $compraSaldoRepository = new CompraSaldoRepository(),
        private $crypto = new Crypto()
    ) {}

    public function realizaCompraSaldoCartaoCredito(array $request): ResponseDTO
    {
        try {
            $compraSaldo = $this->_criandoCompraSaldoCredito($request, SituacaoTransacaoEnum::PENDENTE_PAGAMENTO);
            $this->compraSaldoRepository->updateCompraSaldo($compraSaldo);

            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'number' => $compraSaldo->cartao_numero,
                    'exp_month' => $compraSaldo->cartao_mes,
                    'exp_year' => $compraSaldo->cartao_ano,
                    'cvc' => $compraSaldo->cartao_cvv,
                ],
                'billing_details' => [
                    'name' => $compraSaldo->nome,
                    'email' => $compraSaldo->email,
                ],
            ]);

            return new ResponseDTO('sucesso', 'Compra com saldo atualizada com sucesso', $compraSaldo);
        } catch (\Throwable $th) {
            Log::error("Não foi possível criar a transação de compra de saldo, {$th->getMessage()} | file: {$th->getFile()} | linha: {$th->getLine()} | trace: {$th->getTraceAsString()}");
            return new ResponseDTO('erro', "Não foi possível criar a transação de compra de saldo");
        }
    }

    private function _criandoCompraSaldoCredito(array $request, SituacaoTransacaoEnum $situacaoTransacaoEnum): CompraSaldoDTO
    {
        $objeto = $this->crypto->decrypt($request['body']);
        $objeto = json_decode($objeto);
        return new CompraSaldoDTO(
            $objeto->cartaoNumero,
            $objeto->cartaoCvv,
            $objeto->cartaoMes,
            $objeto->cartaoAno,
            $objeto->oidCartao,
            $objeto->cpf,
            $objeto->valorCompra,
            $situacaoTransacaoEnum,
            $objeto->nome,
            $objeto->email,
            now()->format('Y-m-d H:i:s')
        );
    }
}
