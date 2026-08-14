<?php

namespace App\Services\ENF\Constancias;

use App\Jobs\GenerarPdfConstanciaFinalizacionEnf;
use App\Models\Constancias\ConstanciaCorrelativo;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfConstanciaFinalizacion;
use App\Models\ENF\EnfInformeFinal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmitirConstanciaFinalizacionEnf
{
    public function __construct(
        private readonly NumeroConstanciaFinalizacionEnf $numeros,
        private readonly AutoridadEmisoraConstanciaEnfResolver $autoridad,
    ) {}

    public function emitir(EnfAccion $accion, EnfInformeFinal $informe, ?int $emitidaPor = null): EnfConstanciaFinalizacion
    {
        return DB::transaction(function () use ($accion, $informe, $emitidaPor): EnfConstanciaFinalizacion {
            $accion = EnfAccion::query()->lockForUpdate()->findOrFail($accion->id);
            $informe = EnfInformeFinal::query()->lockForUpdate()->findOrFail($informe->id);

            $existente = EnfConstanciaFinalizacion::query()
                ->where('enf_informe_final_id', $informe->id)
                ->lockForUpdate()
                ->first();

            if ($existente && ! in_array($existente->estado, [EnfConstanciaFinalizacion::ESTADO_PENDIENTE, EnfConstanciaFinalizacion::ESTADO_ERROR], true)) {
                return $existente;
            }

            $this->validarCierre($accion, $informe);

            if ($existente) {
                $constancia = $existente;
            } else {
                $fecha = now();
                $anio = (int) $fecha->year;
                $correlativo = $this->reservarCorrelativo($anio);
                $numero = $this->numeros->format($correlativo, $anio);
                $token = bin2hex(random_bytes(32));
                $codigo = strtoupper(Str::random(10));

                $constancia = EnfConstanciaFinalizacion::create([
                    'enf_accion_id' => $accion->id,
                    'enf_informe_final_id' => $informe->id,
                    'numero' => $numero,
                    'anio' => $anio,
                    'correlativo' => $correlativo,
                    'codigo_validacion' => $codigo,
                    'token_hash' => hash('sha256', $token),
                    'token_cifrado' => Crypt::encryptString($token),
                    'snapshot' => $this->snapshot($accion, $informe, $numero, $codigo, $fecha),
                    'fecha_emision' => $fecha,
                    'emitida_por' => $emitidaPor,
                    'estado' => EnfConstanciaFinalizacion::ESTADO_PENDIENTE,
                ]);
            }

            DB::afterCommit(fn () => GenerarPdfConstanciaFinalizacionEnf::dispatch($constancia->id)->afterCommit());

            return $constancia;
        });
    }

    private function reservarCorrelativo(int $anio): int
    {
        $registro = ConstanciaCorrelativo::query()
            ->where('tipo', NumeroConstanciaFinalizacionEnf::TIPO)
            ->where('anio', $anio)
            ->where('unidad_emisora', NumeroConstanciaFinalizacionEnf::UNIDAD_EMISORA)
            ->lockForUpdate()
            ->first();

        if (! $registro) {
            try {
                $registro = ConstanciaCorrelativo::create([
                    'tipo' => NumeroConstanciaFinalizacionEnf::TIPO,
                    'anio' => $anio,
                    'unidad_emisora' => NumeroConstanciaFinalizacionEnf::UNIDAD_EMISORA,
                    'ultimo_correlativo' => 0,
                ]);
            } catch (QueryException) {
                $registro = ConstanciaCorrelativo::query()
                    ->where('tipo', NumeroConstanciaFinalizacionEnf::TIPO)
                    ->where('anio', $anio)
                    ->where('unidad_emisora', NumeroConstanciaFinalizacionEnf::UNIDAD_EMISORA)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $registro = ConstanciaCorrelativo::query()->whereKey($registro->id)->lockForUpdate()->firstOrFail();
        }

        $registro->increment('ultimo_correlativo');

        return (int) $registro->fresh()->ultimo_correlativo;
    }

    private function validarCierre(EnfAccion $accion, EnfInformeFinal $informe): void
    {
        if (strtoupper((string) $accion->estado_flujo) !== 'FINALIZADO'
            || $informe->estado !== EnfInformeFinal::ESTADO_APROBADO
            || ! $informe->fecha_aprobacion) {
            throw new RuntimeException('El cierre ENF no reune las condiciones para emitir la constancia de finalizacion.');
        }
    }

    private function snapshot(EnfAccion $accion, EnfInformeFinal $informe, string $numero, string $codigo, $fecha): array
    {
        $accion->loadMissing(['tipoAccion', 'centroFacultad', 'departamentoAcademico', 'carrera', 'creadoPor.empleado', 'certificado']);
        $informe->loadMissing(['participantesFinales']);
        $responsable = $accion->creadoPor?->empleado;
        $autoridad = $this->autoridad->resolver($accion, EnfAccion::PROCESO_INFORME_FINAL, 'aplica_cierre_proyecto');

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
                'fecha_inicio' => optional($accion->fecha_inicio)->format('d/m/Y') ?: 'No registrado',
                'fecha_fin' => optional($accion->fecha_finalizacion)->format('d/m/Y') ?: 'No registrado',
                'estado_aprobado' => 'Finalizado',
            ],
            'informe' => [
                'fecha_presentacion' => optional($informe->fecha_presentacion)->format('d/m/Y') ?: 'No registrado',
                'fecha_aprobacion' => optional($informe->fecha_aprobacion)->format('d/m/Y') ?: 'No registrado',
                'participantes' => (int) $informe->participantesFinales->count(),
            ],
            'responsable' => [
                'nombre' => $responsable?->nombre_completo ?: ($accion->creadoPor?->name ?: 'No registrado'),
                'correo' => $accion->creadoPor?->email ?: 'No registrado',
            ],
            'autoridad' => $autoridad,
        ];
    }
}
