<?php

namespace App\Http\Controllers\Constancias;

use App\Http\Controllers\Controller;
use App\Models\Constancias\ConstanciaRegistroProyecto;
use App\Support\DownloadFilename;
use Illuminate\Support\Facades\Storage;

class VerificarConstanciaRegistroController extends Controller
{
    public function __invoke(string $token)
    {
        $constancia = $this->constanciaPorToken($token);
        $vigente = $this->esVigente($constancia);

        return view('constancias.verificacion-registro', [
            'constancia' => $constancia,
            'token' => $token,
            'vigente' => $vigente,
            'puedeDescargarPublicamente' => $vigente && $constancia->puedeDescargarse() && Storage::disk('local')->exists($constancia->ruta_archivo),
            'datos' => [
                'numero' => $constancia->numero,
                'tipo' => 'Constancia de Registro',
                'proyecto' => data_get($constancia->snapshot, 'proyecto.nombre', 'No registrado'),
                'codigo' => data_get($constancia->snapshot, 'proyecto.codigo', 'No registrado'),
                'unidad' => data_get($constancia->snapshot, 'proyecto.unidad_academica', 'No registrado'),
                'coordinador' => data_get($constancia->snapshot, 'coordinador.nombre', 'No registrado'),
                'fecha_emision' => $constancia->fecha_emision?->format('d/m/Y'),
            ],
        ]);
    }

    public function descargar(string $token)
    {
        $constancia = $this->constanciaPorToken($token);

        abort_unless($this->esVigente($constancia) && $constancia->puedeDescargarse(), 404);
        abort_unless(Storage::disk('local')->exists($constancia->ruta_archivo), 404);

        if ($constancia->hash_archivo && hash_file('sha256', Storage::disk('local')->path($constancia->ruta_archivo)) !== $constancia->hash_archivo) {
            abort(409, 'La integridad del archivo no pudo verificarse.');
        }

        return Storage::disk('local')->download(
            $constancia->ruta_archivo,
            DownloadFilename::withExtension('Constancia-Registro-'.$constancia->numero, 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }

    private function constanciaPorToken(string $token): ConstanciaRegistroProyecto
    {
        return ConstanciaRegistroProyecto::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
    }

    private function esVigente(ConstanciaRegistroProyecto $constancia): bool
    {
        return $constancia->estado === ConstanciaRegistroProyecto::ESTADO_EMITIDA && blank($constancia->anulada_en);
    }
}
