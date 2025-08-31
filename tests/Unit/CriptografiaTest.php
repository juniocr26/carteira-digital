<?php

namespace Tests\Unit;

use Tests\TestCase;
use CriptoLib\Crypto;

class CriptografiaTest extends TestCase
{
    public function testEncryptAndDecryptJsonData()
    {
        $originalData = [
            "cartaoNumero" => "5162-XXXX-XXXX-3306",
            "cartaoCvv"    => "954",
            "cartaoMes"    => "10",
            "cartaoAno"    => "2028",
            "oidCartao"    => "afaf8814-ee53-4f09-88c0-8c1a1a0d9be1",
            "cpf"          => "09760917637",
            "valorCompra"  => 200
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
