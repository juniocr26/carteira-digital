<?php

namespace Tests\Unit;

use App\Http\Middleware\JwtMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;
use Firebase\JWT\JWT;

class JwtMiddlewareTest extends TestCase
{
    private function createToken(array $overrides = []): string
    {
        $payload = array_merge([
            'user' => env('BASIC_AUTH_USER'),
            'pass' => env('BASIC_AUTH_PASS'),
            'iat'  => time(),
            'exp'  => time() + 3600,
        ], $overrides);

        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }

    private function createRequest(string $jwt, string $method = 'POST', string $uri = '/fake-route'): Request
    {
        $request = Request::create($uri, $method);
        $request->headers->set('Authorization', "Bearer $jwt");
        return $request;
    }

    private function runMiddleware(Request $request): mixed
    {
        $middleware = new JwtMiddleware();
        return $middleware->handle($request, function ($req) {
            return response()->json(['ok' => true], 200);
        });
    }

    public function test_handle_with_valid_token(): void
    {
        $jwt      = $this->createToken();
        $request  = $this->createRequest($jwt);
        $response = $this->runMiddleware($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ok' => true]),
            $response->getContent()
        );
    }

    public function test_handle_token_with_invalid_keys(): void
    {
        $jwt = $this->createToken([
            'user' => env('teste_user'),
            'pass' => env('teste_auth'),
        ]);

        $request  = $this->createRequest($jwt);
        $response = $this->runMiddleware($request);
        $result   = json_decode($response->getContent());

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Token inválido', $result->error);
    }

    public function test_handle_with_expired_token(): void
    {
        $jwt = $this->createToken([
            'iat' => time() - 7200,
            'exp' => time() - 3600,
        ]);

        $request  = $this->createRequest($jwt, 'GET');
        $response = $this->runMiddleware($request);
        $result   = json_decode($response->getContent());

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Token inválido', $result->error);
    }
}
