<?php
namespace App\Http\Middleware;
use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\Request;
class ApiEmployeeToken
{
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        $expected = (string) config('services.nexo_api.token');
        $provided = (string) ($request->bearerToken() ?: $request->header('X-NEXO-API-TOKEN'));
        if ($provided !== '') {
            $stored = ApiAccessToken::with('scopes')->where('token_hash', ApiAccessToken::hashToken($provided))->first();
            if ($stored?->vigente() && ($scope === null || $stored->scopes->contains('codigo', $scope))) {
                $stored->updateQuietly(['ultimo_uso_en' => now()]);
                try {
                    activity()->performedOn($stored)
                        ->withProperties(['prefijo' => $stored->prefijo, 'alcance' => $scope])
                        ->log('Uso de token API');
                } catch (\Throwable $e) {
                    report($e);
                }
                return $next($request);
            }
        }
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => $provided === '' ? 'Token de API requerido.' : 'Token de API inválido.'], 401);
        }
        return $next($request);
    }
}
