<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Firebase\JWT\JWT;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {


        $basicUser = getenv('BASIC_AUTH_USER') ?: 'usuario_teste';
        $basicPass = getenv('BASIC_AUTH_PASS') ?: 'senha_teste';

        $secret = getenv('JWT_SECRET') ?: 'sua_chave_secreta';

        // Payload do token
        $payload = [
            'user' => $basicUser,
            'pass' => $basicPass,
            'iat'  => time(),
            'exp'  => time() + 3600 // token expira em 1 hora
        ];

        // Gera o token JWT
        $jwt = JWT::encode($payload, $secret, 'HS256');

        dd($jwt);
    }
}
