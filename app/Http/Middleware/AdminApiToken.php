<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AdminApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);
        if ($accessToken === null || $accessToken->tokenable === null) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $request->setUserResolver(fn () => $accessToken->tokenable);
        $request->attributes->set('accessToken', $accessToken);
        $accessToken->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
