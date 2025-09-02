<?php

namespace Tests\Unit;

use Tests\TestCase;
use CriptoLib\Crypto;

class CriptografiaTest extends TestCase
{
    public function testEncryptAndDecryptJsonData(): void
    {
        $originalData = [
            "cartaoNumero" => "9999-9999-9999-999",
            "cartaoCvv"    => "999",
            "cartaoMes"    => "01",
            "cartaoAno"    => "2099",
            "oidCartao"    => "afaf8814-ee53-4f09-88c0-8c1a1a0d9be1",
            "cpf"          => "00000000000",
            "valorCompra"  => 1,
            "nome"         => "Teste Nome",
            "email"        => "Teste Email"
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
