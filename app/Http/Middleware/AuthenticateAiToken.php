<?php

namespace App\Http\Middleware;

use App\Models\AiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAiToken
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $plainTextToken = (string) $request->bearerToken();
        if ($plainTextToken === '' || strlen($plainTextToken) > 200) {
            return response()->json(['error' => 'unauthenticated', 'message' => 'A valid bearer token is required.'], 401);
        }

        $token = AiAccessToken::query()->where('token_hash', hash('sha256', $plainTextToken))->first();
        if (! $token || ! $token->isUsable()) {
            return response()->json(['error' => 'unauthenticated', 'message' => 'The token is invalid, expired, or revoked.'], 401);
        }
        if (! $token->can($ability)) {
            return response()->json(['error' => 'forbidden', 'message' => 'This token does not have the required ability.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('ai_access_token', $token);

        return $next($request);
    }
}
