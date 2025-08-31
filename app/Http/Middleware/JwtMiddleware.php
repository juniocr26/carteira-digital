<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Token JWT necessário'], 401);
        }

        // Pega só o token
        $token = substr($authHeader, 7);

        try {
            // Decodifica usando a mesma chave do .env
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $payload = (array) $decoded;
            $now = time();

            if (isset($payload['iat']) && isset($payload['exp'])) {
                if (
                    $payload['user'] !== env('BASIC_AUTH_USER') ||
                    $payload['pass'] !== env('BASIC_AUTH_PASS') ||
                    $now < $payload['iat'] ||
                    $now > $payload['exp']
                )
                {
                    return response()->json(['error' => 'Token inválido ou expirado'], 401);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token inválido ou expirado', 'message' => $e->getMessage()], 401);
        }

        // Se quiser, pode anexar os dados do token à request
        $request->attributes->set('jwt_payload', (array) $decoded);

        return $next($request);
    }
}
