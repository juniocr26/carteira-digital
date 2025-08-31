<?php

namespace Tests\Unit;

use App\Http\Middleware\JwtMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;
use Firebase\JWT\JWT;

class JwtMiddlewareTest extends TestCase
{
    public function test_handle_with_valid_token()
    {
        $basicUser = env('BASIC_AUTH_USER');
        $basicPass = env('BASIC_AUTH_PASS');
        $secret    = env('JWT_SECRET');

        // cria um token válido
        $payload = [
            'user' => $basicUser,
            'pass' => $basicPass,
            'iat'  => time(),
            'exp'  => time() + 3600,
        ];

        $jwt = JWT::encode($payload, $secret, 'HS256');

        // cria uma request fake com Authorization header
        $request = Request::create('/fake-route', 'POST');
        $request->headers->set('Authorization', "Bearer $jwt");

        $middleware = new JwtMiddleware();

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['ok' => true], 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ok' => true]),
            $response->getContent()
        );
    }

    public function test_handle_token_with_invalid_keys()
    {
        $basicUser = env('teste_user');
        $basicPass = env('teste_auth');
        $secret    = env('JWT_SECRET');

        // cria um token válido
        $payload = [
            'user' => $basicUser,
            'pass' => $basicPass,
            'iat'  => time(),
            'exp'  => time() + 3600,
        ];

        $jwt = JWT::encode($payload, $secret, 'HS256');

        // cria uma request fake com Authorization header
        $request = Request::create('/fake-route', 'POST');
        $request->headers->set('Authorization', "Bearer $jwt");

        $middleware = new JwtMiddleware();

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['ok' => true], 200);
        });
        $result = json_decode($response->getContent());

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Token inválido', $result->error);
    }

    public function test_handle_with_expired_token()
    {
        $basicUser = env('BASIC_AUTH_USER');
        $basicPass = env('BASIC_AUTH_PASS');
        $secret    = env('JWT_SECRET');

        // token expirado
        $payload = [
            'user' => $basicUser,
            'pass' => $basicPass,
            'iat'  => time() - 7200,
            'exp'  => time() - 3600,
        ];

        $jwt = JWT::encode($payload, $secret, 'HS256');

        $request = Request::create('/fake-route', 'GET');
        $request->headers->set('Authorization', "Bearer $jwt");

        $middleware = new JwtMiddleware();

        $response = $middleware->handle($request, function ($req) {
            return response()->json(['ok' => true], 200);
        });
        $result = json_decode($response->getContent());

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Token inválido', $result->error);
    }
}
