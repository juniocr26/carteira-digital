<?php

namespace Tests\Unit;

use Tests\TestCase;
use CriptoLib\Crypto;

class CriptografiaTest extends TestCase
{
    public function testEncryptAndDecryptJsonData(): void
    {
        $originalData = [
            "payment_method_id"     => "pm_1S2tzoJmY2H3x9sXSFZ9VWcd",
            "valor_compra"          => "100",
            "nome"                  => "Teste Nome",
            "cpf"                   => "00000000000",
            "descricao_transacao"   => "Teste Descrição Transação",
            "tipo_transacao"        => "Transacao"
        ];


        $jsonData = json_encode($originalData);
        $crypto = new Crypto();
        $encrypted = $crypto->encrypt($jsonData);
        $decrypted = $crypto->decrypt($encrypted);


        $decodedData = json_decode($decrypted, true);
        foreach ($originalData as $key => $value) {
            $this->assertEquals($value, $decodedData[$key], "Falha no campo: $key");
        }
    }
}
