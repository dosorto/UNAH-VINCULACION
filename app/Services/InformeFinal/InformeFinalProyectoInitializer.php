<?php

namespace App\Services\InformeFinal;

use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\Proyecto;
use Illuminate\Support\Facades\DB;

class InformeFinalProyectoInitializer
{
    public function initialize(Proyecto $proyecto, ?int $userId = null): InformeFinalProyecto
    {
        return DB::transaction(function () use ($proyecto, $userId) {
            $proyecto = Proyecto::query()->lockForUpdate()->findOrFail($proyecto->getKey());
            $proyecto->load([
                'modalidad', 'categoria', 'facultades_centros', 'departamentos_academicos', 'carreras',
                'departamento', 'municipio', 'ciudad', 'ejes_prioritarios_unah',
                'coordinador_proyecto.empleado.user', 'coordinador_proyecto.empleado.categoria',
                'coordinador_proyecto.empleado.departamento_academico',
                'docentes_proyecto.empleado.user', 'docentes_proyecto.empleado.categoria',
                'docentes_proyecto.empleado.departamento_academico',
                'estudiante_proyecto.estudiante.carrera', 'estudiante_proyecto.carrera',
                'integrante_internacional_proyecto.integranteInternacional', 'entidad_contraparte',
                'objetivosEspecificos.resultados', 'actividades.empleados', 'aportesInstitucionales',
                'presupuesto', 'ods', 'metasContribuye',
            ]);

            $existente = InformeFinalProyecto::withTrashed()->where('proyecto_id', $proyecto->id)->first();

            if ($existente) {
                $this->completarSnapshotsFaltantes($existente, $proyecto);

                return $existente->load($this->relations());
            }

            $facultad = $proyecto->facultades_centros->first();
            $departamentoAcademico = $proyecto->departamentos_academicos->first();
            $carrera = $proyecto->carreras->first();
            $categoria = $proyecto->categoria->first();
            $departamentoTerritorial = $proyecto->departamento->first();
            $municipio = $proyecto->municipio->first();
            $presupuestoPlanificado = (float) $proyecto->aportesInstitucionales->sum('costo_total')
                + (float) optional($proyecto->presupuesto)->aporte_contraparte
                + (float) optional($proyecto->presupuesto)->aporte_comunidad
                + (float) optional($proyecto->presupuesto)->aporte_internacionales
                + (float) optional($proyecto->presupuesto)->aporte_otras_universidades
                + (float) optional($proyecto->presupuesto)->otros_aportes;

            $informe = InformeFinalProyecto::create([
                'proyecto_id' => $proyecto->id,
                'numero_registro' => $this->numeroRegistroOficial($proyecto),
                'fecha_registro' => $proyecto->fecha_registro,
                'nombre_proyecto' => $proyecto->nombre_proyecto ?: 'Sin nombre registrado',
                'objetivo_general' => $proyecto->objetivo_general,
                'centro_facultad_id' => $facultad?->id,
                'departamento_academico_id' => $departamentoAcademico?->id,
                'carrera_id' => $carrera?->id,
                'modalidad_id' => $proyecto->modalidad_id,
                'categoria_id' => $categoria?->id,
                'departamento_territorial_id' => $departamentoTerritorial?->id,
                'municipio_id' => $municipio?->id,
                'facultad_centro' => $facultad?->nombre,
                'unidad_academica' => $facultad?->nombre,
                'departamento_academico' => $departamentoAcademico?->nombre,
                'carrera' => $carrera?->nombre,
                'programa_vinculacion' => $proyecto->programa_pertenece,
                'linea_investigacion' => $proyecto->lineas_investigacion_academica,
                'modalidad' => $proyecto->modalidad?->nombre,
                'ejes_prioritarios' => $proyecto->ejes_prioritarios_unah->pluck('nombre')->implode(', '),
                'categoria' => $categoria?->nombre,
                'fecha_inicio' => $proyecto->fecha_inicio,
                'fecha_finalizacion' => $proyecto->fecha_finalizacion,
                'pais' => $this->texto($proyecto->pais),
                'region' => $this->texto($proyecto->region),
                'departamento_territorial' => $departamentoTerritorial?->nombre,
                'municipio' => $municipio?->nombre,
                'aldea_ciudad' => $proyecto->ciudad?->nombre ?: $this->texto($proyecto->aldea),
                'caserio' => $this->texto($proyecto->caserio),
                'problema_inicial' => $proyecto->definicion_problema,
                'transformacion_lograda' => $proyecto->impacto_deseado,
                'respuesta_reforma_universitaria' => $proyecto->alineamiento_reforma,
                'bibliografia' => $proyecto->bibliografia,
                'valoracion_total_beneficiarios' => max(0, (int) $proyecto->poblacion_participante),
                'presupuesto_planificado' => max(0, $presupuestoPlanificado),
                'estado' => 'BORRADOR',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $informe->beneficiarios()->create([
                'hombres' => max(0, (int) $proyecto->hombres),
                'mujeres' => max(0, (int) $proyecto->mujeres),
                'indigena_hombres' => max(0, (int) $proyecto->indigenas_hombres),
                'indigena_mujeres' => max(0, (int) $proyecto->indigenas_mujeres),
                'afrodescendiente_hombres' => max(0, (int) $proyecto->afroamericanos_hombres),
                'afrodescendiente_mujeres' => max(0, (int) $proyecto->afroamericanos_mujeres),
                'mestizo_hombres' => max(0, (int) $proyecto->mestizos_hombres),
                'mestizo_mujeres' => max(0, (int) $proyecto->mestizos_mujeres),
            ]);

            foreach ($proyecto->docentes_proyecto as $miembro) {
                $empleado = $miembro->empleado;
                if (! $empleado) {
                    continue;
                }
                $informe->equipoDocente()->create([
                    'empleado_id' => $empleado->id,
                    'nombre' => $empleado->nombre_completo,
                    'numero_empleado' => $empleado->numero_empleado,
                    'correo' => $empleado->user?->email,
                    'categoria' => $empleado->categoria?->nombre,
                    'departamento' => $empleado->departamento_academico?->nombre,
                    'sexo' => $empleado->sexo,
                    'tipo_participacion' => $miembro->rol,
                    'es_coordinador' => strcasecmp((string) $miembro->rol, 'Coordinador') === 0,
                ]);
            }

            foreach ($proyecto->integrante_internacional_proyecto as $registro) {
                $persona = $registro->integranteInternacional;
                if ($persona) {
                    $informe->cooperacion()->create([
                        'nombre' => $persona->nombre_completo,
                        'pasaporte' => $persona->documento_identidad,
                        'correo' => $persona->email,
                        'pais' => $persona->pais,
                        'universidad' => $persona->institucion,
                    ]);
                }
            }

            $this->crearSnapshotsEstudiantes($informe, $proyecto);

            foreach ($proyecto->entidad_contraparte as $entidad) {
                $informe->contrapartes()->create([
                    'entidad_contraparte_id' => $entidad->id,
                    'nombre' => $entidad->nombre,
                    'tipo' => $this->tipoContraparte($entidad->tipo_entidad),
                    'contacto' => $entidad->nombre_contacto,
                    'correo' => $entidad->correo,
                    'cargo' => $entidad->cargo_contacto,
                    'telefono' => $entidad->telefono,
                    'compromisos_asumidos' => $entidad->descripcion_acuerdos,
                    'territorio' => collect([$departamentoTerritorial?->nombre, $municipio?->nombre])->filter()->implode(', '),
                ]);
            }

            foreach ($proyecto->objetivosEspecificos as $objetivo) {
                foreach ($objetivo->resultados as $resultado) {
                    $informe->resultados()->create([
                        'resultado_esperado_id' => $resultado->id,
                        'objetivo_especifico' => $objetivo->descripcion,
                        'resultado_planificado' => $resultado->nombre_resultado ?: $objetivo->descripcion,
                        'indicador_propuesto' => $resultado->nombre_indicador,
                        'unidad_medida' => $resultado->nombre_medio_verificacion,
                    ]);
                }
            }

            foreach ($proyecto->actividades as $actividad) {
                $snapshot = $informe->actividades()->create([
                    'actividad_id' => $actividad->id,
                    'actividad_planificada' => $actividad->descripcion,
                    'responsable' => $actividad->empleados->first()?->nombre_completo,
                    'fecha_inicial' => $actividad->fecha_inicio,
                    'fecha_final' => $actividad->fecha_finalizacion,
                    'horas_dedicadas' => max(0, (float) $actividad->horas),
                ]);
                $this->crearParticipantesActividad($snapshot, $actividad->empleados);
            }

            foreach ($proyecto->aportesInstitucionales as $aporte) {
                $total = max(0, (float) $aporte->costo_total);
                $informe->presupuestoDetalles()->create([
                    'fuente' => 'UNAH',
                    'concepto' => $aporte->concepto_label ?: 'Aporte institucional',
                    'unidad' => 'aporte',
                    'cantidad' => 1,
                    'costo_unitario' => $total,
                ]);
            }

            foreach ($proyecto->ods as $ods) {
                $metas = $proyecto->metasContribuye->where('ods_id', $ods->id);
                if ($metas->isEmpty()) {
                    $informe->ods()->create(['ods_id' => $ods->id, 'nivel_contribucion' => 'directa']);
                } else {
                    foreach ($metas as $meta) {
                        $informe->ods()->create([
                            'ods_id' => $ods->id,
                            'meta_contribuye_id' => $meta->id,
                            'meta_ods' => trim('Meta '.$meta->numero_meta.': '.$meta->descripcion),
                            'nivel_contribucion' => 'directa',
                        ]);
                    }
                }
            }

            return $informe->load($this->relations());
        }, 3);
    }

    private function relations(): array
    {
        return ['proyecto', 'beneficiarios', 'equipoDocente', 'cooperacion', 'estudiantes', 'voluntarios', 'contrapartes', 'resultados', 'actividades.participantes', 'accionesNoEjecutadas', 'accionesEmergentes', 'ods.ods', 'ods.meta', 'presupuestoDetalles', 'anexos'];
    }

    private function completarSnapshotsFaltantes(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        if ($informe->estado !== 'BORRADOR') {
            return;
        }

        // Corrige únicamente el valor heredado que expuso un ID interno.
        if (preg_match('/^Proyecto #\d+$/', trim((string) $informe->numero_registro))) {
            $informe->update(['numero_registro' => $this->numeroRegistroOficial($proyecto)]);
        }

        $informe->load(['estudiantes', 'voluntarios', 'actividades.participantes']);

        foreach ($informe->estudiantes->whereNull('sexo') as $snapshot) {
            $registro = $proyecto->estudiante_proyecto->firstWhere('estudiante_id', $snapshot->estudiante_id);
            if (! $registro || $snapshot->created_at?->ne($snapshot->updated_at) || (float) $snapshot->horas_dedicadas !== 0.0) {
                continue;
            }
            $this->repararSnapshotEstudiante($informe, $snapshot, $registro);
        }

        foreach ($informe->actividades as $snapshot) {
            if ($snapshot->participantes->isNotEmpty() || ! $snapshot->actividad_id) {
                continue;
            }
            $actividad = $proyecto->actividades->firstWhere('id', $snapshot->actividad_id);
            if ($actividad) {
                $this->crearParticipantesActividad($snapshot, $actividad->empleados);
                if (blank($snapshot->responsable)) {
                    $snapshot->update(['responsable' => $actividad->empleados->first()?->nombre_completo]);
                }
            }
        }
    }

    private function crearSnapshotsEstudiantes(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        foreach ($proyecto->estudiante_proyecto as $registro) {
            $estudiante = $registro->estudiante;
            $base = [
                'estudiante_id' => $estudiante?->id,
                'nombre' => $estudiante ? trim($estudiante->nombre.' '.$estudiante->apellido) : '',
                'numero_cuenta' => $estudiante?->cuenta,
                'carrera' => $estudiante?->carrera?->nombre ?: $registro->carrera?->nombre,
                'correo' => $estudiante?->user?->email,
                'tipo_participacion' => $this->tipoParticipacionEstudiante($registro->tipo_participacion_estudiante ?? $estudiante?->tipo_participacion_estudiante),
            ];
            $informe->estudiantes()->create($base + [
                'sexo' => $estudiante?->sexo,
                'cantidad' => 1,
                'origen' => 'PROYECTO',
            ]);
        }
    }

    private function repararSnapshotEstudiante(InformeFinalProyecto $informe, $snapshot, $registro): void
    {
        if (filled($registro->estudiante?->sexo)) {
            $snapshot->update(['sexo' => $registro->estudiante->sexo, 'cantidad' => 1]);
        }
    }

    private function crearParticipantesActividad($snapshot, $empleados): void
    {
        foreach ($empleados->unique('id')->values() as $orden => $empleado) {
            $snapshot->participantes()->firstOrCreate(
                ['tipo' => 'docente', 'empleado_id' => $empleado->id],
                ['nombre' => $empleado->nombre_completo, 'rol' => $orden === 0 ? 'Responsable principal' : 'Participante', 'es_responsable' => $orden === 0, 'orden' => $orden]
            );
        }
    }


    private function texto(mixed $valor): ?string
    {
        if (is_array($valor)) {
            return collect($valor)->filter()->implode(', ') ?: null;
        }
        return filled($valor) ? (string) $valor : null;
    }

    private function numeroRegistroOficial(Proyecto $proyecto): ?string
    {
        return $this->texto($proyecto->codigo_proyecto);
    }

    private function tipoParticipacionEstudiante(?string $tipo): string
    {
        $tipo = mb_strtolower((string) $tipo);
        return str_contains($tipo, 'volunt') ? 'voluntariado'
            : (str_contains($tipo, 'pps') || str_contains($tipo, 'servicio') ? 'pps_servicio_social' : 'practica_asignatura');
    }

    private function tipoContraparte(?string $tipo): string
    {
        $tipo = mb_strtolower((string) $tipo);
        return match (true) {
            str_contains($tipo, 'municip') => 'gobierno_municipal',
            str_contains($tipo, 'gobierno') => 'gobierno_nacional',
            str_contains($tipo, 'ong') => 'ong',
            str_contains($tipo, 'privad') => 'sector_privado',
            str_contains($tipo, 'internacional') => 'internacional',
            default => 'sociedad_civil',
        };
    }
}
