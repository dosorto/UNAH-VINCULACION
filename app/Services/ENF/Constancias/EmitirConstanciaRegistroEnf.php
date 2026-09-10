<?php

namespace App\Services\ENF\Constancias;

use App\Jobs\GenerarPdfConstanciaRegistroEnf;
use App\Models\Constancias\ConstanciaCorrelativo;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfConstanciaRegistro;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmitirConstanciaRegistroEnf
{
    public function __construct(
        private readonly NumeroConstanciaRegistroEnf $numeros,
        private readonly AutoridadEmisoraConstanciaEnfResolver $autoridad,
    ) {}

    public function emitir(EnfAccion $accion, ?int $emitidaPor = null): EnfConstanciaRegistro
    {
        return DB::transaction(function () use ($accion, $emitidaPor): EnfConstanciaRegistro {
            $accion = EnfAccion::query()->lockForUpdate()->findOrFail($accion->id);

            $existente = EnfConstanciaRegistro::query()
                ->where('enf_accion_id', $accion->id)
                ->lockForUpdate()
                ->first();

            if ($existente && ! in_array($existente->estado, [EnfConstanciaRegistro::ESTADO_PENDIENTE, EnfConstanciaRegistro::ESTADO_ERROR], true)) {
                return $existente;
            }

            $this->validarInscripcion($accion);

            if ($existente) {
                $constancia = $existente;
            } else {
                $fecha = now();
                $anio = (int) $fecha->year;
                $correlativo = $this->reservarCorrelativo($anio);
                $numero = $this->numeros->format($correlativo, $anio);
                $token = bin2hex(random_bytes(32));
                $codigo = strtoupper(Str::random(10));

                $constancia = EnfConstanciaRegistro::create([
                    'enf_accion_id' => $accion->id,
                    'numero' => $numero,
                    'anio' => $anio,
                    'correlativo' => $correlativo,
                    'codigo_validacion' => $codigo,
                    'token_hash' => hash('sha256', $token),
                    'token_cifrado' => Crypt::encryptString($token),
                    'snapshot' => $this->snapshot($accion, $numero, $codigo, $fecha),
                    'fecha_emision' => $fecha,
                    'emitida_por' => $emitidaPor,
                    'estado' => EnfConstanciaRegistro::ESTADO_PENDIENTE,
                ]);
            }

            DB::afterCommit(fn () => GenerarPdfConstanciaRegistroEnf::dispatch($constancia->id)->afterCommit());

            return $constancia;
        });
    }

    private function reservarCorrelativo(int $anio): int
    {
        $registro = ConstanciaCorrelativo::query()
            ->where('tipo', NumeroConstanciaRegistroEnf::TIPO)
            ->where('anio', $anio)
            ->where('unidad_emisora', NumeroConstanciaRegistroEnf::UNIDAD_EMISORA)
            ->lockForUpdate()
            ->first();

        if (! $registro) {
            try {
                $registro = ConstanciaCorrelativo::create([
                    'tipo' => NumeroConstanciaRegistroEnf::TIPO,
                    'anio' => $anio,
                    'unidad_emisora' => NumeroConstanciaRegistroEnf::UNIDAD_EMISORA,
                    'ultimo_correlativo' => 0,
                ]);
            } catch (QueryException) {
                $registro = ConstanciaCorrelativo::query()
                    ->where('tipo', NumeroConstanciaRegistroEnf::TIPO)
                    ->where('anio', $anio)
                    ->where('unidad_emisora', NumeroConstanciaRegistroEnf::UNIDAD_EMISORA)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $registro = ConstanciaCorrelativo::query()->whereKey($registro->id)->lockForUpdate()->firstOrFail();
        }

        $registro->increment('ultimo_correlativo');

        return (int) $registro->fresh()->ultimo_correlativo;
    }

    private function validarInscripcion(EnfAccion $accion): void
    {
        if (strtoupper((string) $accion->estado_flujo) !== 'APROBADO') {
            throw new RuntimeException('La inscripcion ENF aun no esta aprobada.');
        }

        $ciclo = (int) $accion->revisiones()
            ->where('proceso', EnfAccion::PROCESO_INSCRIPCION)
            ->max('revision_ciclo');

        if ($ciclo < 1 || $accion->revisiones()
            ->where('proceso', EnfAccion::PROCESO_INSCRIPCION)
            ->where('revision_ciclo', $ciclo)
            ->where('estado', '!=', 'APROBADO')
            ->exists()) {
            throw new RuntimeException('El flujo de inscripcion ENF no ha completado todas las etapas.');
        }
    }

    private function snapshot(EnfAccion $accion, string $numero, string $codigo, $fecha): array
    {
        $accion->loadMissing(['tipoAccion', 'centroFacultad', 'departamentoAcademico', 'carrera', 'creadoPor.empleado', 'certificado']);
        $responsable = $accion->creadoPor?->empleado;
        $autoridad = $this->autoridad->resolver($accion, EnfAccion::PROCESO_INSCRIPCION, 'aplica_inscripcion');

        return [
            'constancia' => [
                'numero' => $numero,
                'codigo_validacion' => $codigo,
                'fecha_emision' => $fecha->toIso8601String(),
                'ciudad_emision' => 'Ciudad Universitaria Jose Trinidad Reyes',
            ],
            'accion' => [
                'id_referencia' => (string) $accion->id,
                'codigo_formulario' => $accion->codigo_formulario ?: 'No registrado',
                'numero_registro' => $accion->numero_registro ?: 'No registrado',
                'nombre' => $accion->nombre_accion ?: 'No registrado',
                'tipo' => $accion->tipoAccion?->nombre ?: 'Educacion no formal',
                'unidad_academica' => $accion->centroFacultad?->nombre ?: 'No registrado',
                'departamento' => $accion->departamentoAcademico?->nombre ?: 'No registrado',
                'fecha_inicio' => optional($accion->fecha_inicio)->format('d/m/Y') ?: 'No registrado',
                'fecha_fin' => optional($accion->fecha_finalizacion)->format('d/m/Y') ?: 'No registrado',
                'estado_aprobado' => 'Inscrito',
            ],
            'responsable' => [
                'nombre' => $responsable?->nombre_completo ?: ($accion->creadoPor?->name ?: 'No registrado'),
                'correo' => $accion->creadoPor?->email ?: 'No registrado',
            ],
            'autoridad' => $autoridad,
        ];
    }
}
