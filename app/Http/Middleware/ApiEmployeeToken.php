<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class ApiEmployeeToken
{
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        $provided = (string) ($request->bearerToken() ?: $request->header('X-NEXO-API-TOKEN'));
        if ($provided === '') {
            return response()->json(['message' => 'Token de API requerido.'], 401);
        }

        try {
            $stored = ApiAccessToken::with('scopes')->where('token_hash', ApiAccessToken::hashToken($provided))->first();
        } catch (Throwable) {
            return response()->json(['message' => 'No fue posible autenticar la solicitud.'], 500);
        }

        if ($stored !== null) {
            // Un token conocido nunca puede recuperarse mediante la configuración legada.
            if (! $stored->vigente()) {
                return response()->json(['message' => 'Token de API inválido.'], 401);
            }
            if ($scope !== null && ! $stored->scopes->contains(fn ($assigned) => $assigned->codigo === $scope && $assigned->activo)) {
                return response()->json(['message' => 'El token no tiene el alcance requerido.'], 403);
            }

            try {
                $stored->updateQuietly(['ultimo_uso_en' => now()]);
            } catch (Throwable $e) {
                $this->reportSafely($e);
            }
            try {
                activity()->performedOn($stored)
                    ->withProperties(['prefijo' => $stored->prefijo, 'alcance' => $scope])
                    ->log('Uso de token API');
            } catch (Throwable $e) {
                $this->reportSafely($e);
            }

            return $next($request);
        }

        $expected = (string) config('services.nexo_api.token');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Token de API inválido.'], 401);
        }

        return $next($request);
    }

    private function reportSafely(Throwable $exception): void
    {
        try {
            report($exception);
        } catch (Throwable) {
            // La telemetría no debe bloquear una solicitud autorizada.
        }
    }
}
