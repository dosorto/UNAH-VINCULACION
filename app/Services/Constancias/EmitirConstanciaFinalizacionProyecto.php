<?php

namespace App\Services\Constancias;

use App\Jobs\GenerarPdfConstanciaFinalizacionProyecto;
use App\Models\Constancias\ConstanciaCorrelativo;
use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\Proyecto;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use RuntimeException;

class EmitirConstanciaFinalizacionProyecto
{
    public function __construct(
        private readonly NumeroConstanciaFinalizacion $numeros,
        private readonly AutoridadEmisoraConstanciaResolver $autoridad,
    ) {}

    public function emitir(Proyecto $proyecto, InformeFinalProyecto $informe, DocumentoProyecto $documento, ?int $emitidaPor = null): ConstanciaFinalizacionProyecto
    {
        return DB::transaction(function () use ($proyecto, $informe, $documento, $emitidaPor): ConstanciaFinalizacionProyecto {
            $proyecto = Proyecto::query()->lockForUpdate()->findOrFail($proyecto->id);
            $informe = InformeFinalProyecto::query()->lockForUpdate()->findOrFail($informe->id);
            $documento = DocumentoProyecto::query()->lockForUpdate()->findOrFail($documento->id);

            $existente = ConstanciaFinalizacionProyecto::query()
                ->where('informe_final_proyecto_id', $informe->id)
                ->lockForUpdate()
                ->first();

            if ($existente && ! in_array($existente->estado, [ConstanciaFinalizacionProyecto::ESTADO_PENDIENTE, ConstanciaFinalizacionProyecto::ESTADO_ERROR], true)) {
                return $existente;
            }

            $this->validarCierre($proyecto, $informe, $documento);

            if ($existente) {
                $constancia = $existente;
            } else {
                $fecha = now();
                $anio = (int) $fecha->year;
                $correlativo = $this->reservarCorrelativo($anio);
                $numero = $this->numeros->format($correlativo, $anio);
                $token = bin2hex(random_bytes(32));
                $codigo = strtoupper(Str::random(10));

                $constancia = ConstanciaFinalizacionProyecto::create([
                    'proyecto_id' => $proyecto->id,
                    'informe_final_proyecto_id' => $informe->id,
                    'documento_proyecto_id' => $documento->id,
                    'numero' => $numero,
                    'anio' => $anio,
                    'correlativo' => $correlativo,
                    'codigo_validacion' => $codigo,
                    'token_hash' => hash('sha256', $token),
                    'token_cifrado' => Crypt::encryptString($token),
                    'snapshot' => $this->snapshot($proyecto, $informe, $documento, $numero, $codigo, $fecha),
                    'fecha_emision' => $fecha,
                    'emitida_por' => $emitidaPor,
                    'estado' => ConstanciaFinalizacionProyecto::ESTADO_PENDIENTE,
                ]);
            }

            DB::afterCommit(fn () => GenerarPdfConstanciaFinalizacionProyecto::dispatch($constancia->id)->afterCommit());

            return $constancia;
        });
    }

    private function reservarCorrelativo(int $anio): int
    {
        $registro = ConstanciaCorrelativo::query()
            ->where('tipo', NumeroConstanciaFinalizacion::TIPO)
            ->where('anio', $anio)
            ->where('unidad_emisora', NumeroConstanciaFinalizacion::UNIDAD_EMISORA)
            ->lockForUpdate()
            ->first();

        if (! $registro) {
            try {
                $registro = ConstanciaCorrelativo::create([
                    'tipo' => NumeroConstanciaFinalizacion::TIPO,
                    'anio' => $anio,
                    'unidad_emisora' => NumeroConstanciaFinalizacion::UNIDAD_EMISORA,
                    'ultimo_correlativo' => 0,
                ]);
            } catch (QueryException) {
                // Otro cierre creó el correlativo anual en paralelo; se bloquea la fila ya existente.
                $registro = ConstanciaCorrelativo::query()
                    ->where('tipo', NumeroConstanciaFinalizacion::TIPO)
                    ->where('anio', $anio)
                    ->where('unidad_emisora', NumeroConstanciaFinalizacion::UNIDAD_EMISORA)
                    ->lockForUpdate()
                    ->firstOrFail();
            }
            $registro = ConstanciaCorrelativo::query()->whereKey($registro->id)->lockForUpdate()->firstOrFail();
        }

        $registro->increment('ultimo_correlativo');

        return (int) $registro->fresh()->ultimo_correlativo;
    }

    private function validarCierre(Proyecto $proyecto, InformeFinalProyecto $informe, DocumentoProyecto $documento): void
    {
        $estadoDocumento = $documento->estado?->tipoestado?->nombre;
        $estadoProyecto = $proyecto->estado?->tipoestado?->nombre;
        $ciclo = (int) $documento->firma_documento()->max('revision_ciclo');
        $flujoId = $documento->firma_documento()->where('revision_ciclo', $ciclo)->value('flujo_aprobacion_id');

        if ($documento->tipo_documento !== 'Informe Final'
            || $estadoDocumento !== 'Aprobado'
            || $estadoProyecto !== 'Finalizado'
            || ! $informe->fecha_cierre
            || $ciclo < 1
            || ! $flujoId
            || ! $proyecto->firmasDeEtapasCompletadas((int) $flujoId, $ciclo, $documento)) {
            throw new RuntimeException('El flujo de cierre no reúne las condiciones para emitir la constancia de finalización.');
        }
    }

    private function snapshot(Proyecto $proyecto, InformeFinalProyecto $informe, DocumentoProyecto $documento, string $numero, string $codigo, $fecha): array
    {
        $informe->loadMissing(['beneficiarios', 'equipoDocente', 'estudiantes', 'voluntarios', 'presupuestoDetalles']);
        $autoridad = $this->autoridad->resolver($documento);
        $equipo = $informe->equipoDocente
            ->where('estado_participacion', '!=', 'removido')
            ->map(fn ($persona) => [
                'nombre' => $persona->nombre ?: 'No registrado',
                'numero_empleado' => $persona->numero_empleado ?: 'No registrado',
                'categoria' => $persona->categoria ?: 'No registrado',
                'departamento' => $persona->departamento ?: 'No registrado',
                'horas' => (string) $persona->horas_dedicadas,
                'rol' => $persona->tipo_participacion ?: 'Participante',
                'es_coordinador' => (bool) $persona->es_coordinador,
            ])->values()->all();
        $coordinador = collect($equipo)->firstWhere('es_coordinador', true) ?: [
            'nombre' => 'No registrado', 'numero_empleado' => 'No registrado', 'categoria' => 'No registrado',
            'departamento' => 'No registrado', 'horas' => '0.00', 'rol' => 'Coordinador/a del proyecto',
        ];
        $beneficiarios = $informe->beneficiarios;
        $voluntarios = $informe->voluntarios->where('estado_participacion', '!=', 'removido');
        $estudiantes = $informe->estudiantes->where('estado_participacion', '!=', 'removido');

        return [
            'constancia' => ['numero' => $numero, 'codigo_validacion' => $codigo, 'fecha_emision' => $fecha->toIso8601String(), 'ciudad_emision' => 'Ciudad Universitaria José Trinidad Reyes'],
            'proyecto' => [
                'id_referencia' => (string) $proyecto->id,
                'codigo' => $informe->numero_registro ?: ($proyecto->codigo_proyecto ?: 'No registrado'),
                'nombre' => $informe->nombre_proyecto ?: 'No registrado',
                'unidad_academica' => $informe->unidad_academica ?: ($informe->facultad_centro ?: 'No registrado'),
                'centro_regional' => $informe->facultad_centro ?: 'No registrado',
                'categoria' => $informe->categoria ?: 'No registrado',
                'comunidad_beneficiada' => collect([$informe->aldea_ciudad, $informe->municipio, $informe->departamento_territorial])->filter()->implode(', ') ?: 'No registrado',
                'fecha_inicio' => optional($informe->fecha_inicio)->format('d/m/Y') ?: 'No registrado',
                'fecha_fin' => optional($informe->fecha_finalizacion)->format('d/m/Y') ?: 'No registrado',
                'fecha_informe_final' => optional($informe->fecha_cierre)->format('d/m/Y') ?: 'No registrado',
                'periodo_ejecucion' => trim((optional($informe->fecha_inicio)->format('d/m/Y') ?: 'No registrado').' al '.(optional($informe->fecha_finalizacion)->format('d/m/Y') ?: 'No registrado')),
            ],
            'coordinador' => $coordinador,
            'equipo' => array_values(array_filter($equipo, fn ($persona) => ! $persona['es_coordinador'])),
            'beneficiarios' => ['hombres' => (int) ($beneficiarios?->hombres ?? 0), 'mujeres' => (int) ($beneficiarios?->mujeres ?? 0), 'total' => (int) ($beneficiarios?->total_sexo ?? 0)],
            'participacion' => [
                'estudiantes' => (int) $estudiantes->sum('cantidad'),
                'voluntarios_docentes' => (int) $voluntarios->whereIn('tipo', ['profesor_hora', 'profesor_permanente'])->count(),
                'voluntarios_estudiantes' => (int) $estudiantes->where('tipo_participacion', 'voluntariado')->sum('cantidad'),
                'personal_administrativo' => (int) $voluntarios->where('tipo', 'pas')->count(),
            ],
            'presupuesto' => ['moneda' => 'HNL', 'unah' => number_format($informe->total_unah, 2, '.', ''), 'contraparte' => number_format($informe->total_contraparte, 2, '.', ''), 'total' => number_format($informe->ejecucion_total, 2, '.', '')],
            'autoridad' => $autoridad,
        ];
    }
}
