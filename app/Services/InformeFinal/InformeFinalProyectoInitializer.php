<?php

namespace App\Services\InformeFinal;

use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\Proyecto;
use App\Support\InformeFinal\ParticipacionEstudiantil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                'gruposEstudiantesPlanificados.estudiante.carrera', 'gruposEstudiantesPlanificados.estudiante.user',
                'gruposEstudiantesPlanificados.carrera', 'gruposEstudiantesPlanificados.asignatura',
                'integrante_internacional_proyecto.integranteInternacional', 'entidad_contraparte_proyecto.entidadContraparte', 'entidad_contraparte_proyecto.instrumentoFormalizacion',
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

            $this->sincronizarGruposEstudiantes($informe, $proyecto);

            $contrapartesProyecto = $proyecto->entidad_contraparte_proyecto()->with('entidadContraparte')->get();
            $aporteContrapartePlanificado = (float) optional($proyecto->presupuesto)->aporte_contraparte;

            foreach ($contrapartesProyecto as $pivot) {
                $catalogo = $pivot->entidadContraparte;
                $datosContraparte = [
                    'entidad_contraparte_id' => $catalogo->id,
                    'nombre' => $catalogo->nombre,
                    'tipo' => $this->tipoContraparte($catalogo->tipo_entidad),
                    'contacto' => $pivot->nombre_contacto ?: $catalogo->nombre_contacto,
                    'correo' => $pivot->correo ?: $catalogo->correo,
                    'cargo' => $pivot->cargo_contacto ?: $catalogo->cargo_contacto,
                    'telefono' => $pivot->telefono ?: $catalogo->telefono,
                    'compromisos_asumidos' => $pivot->descripcion_acuerdos,
                    'territorio' => collect([$departamentoTerritorial?->nombre, $municipio?->nombre])->filter()->implode(', '),
                    // El presupuesto inicial sólo guarda un monto global. Se asigna únicamente
                    // cuando existe una sola contraparte, para no duplicarlo entre entidades.
                    'aporte_monetario' => $contrapartesProyecto->count() === 1 ? $aporteContrapartePlanificado : 0,
                ];
                if (Schema::hasColumn('informe_final_contrapartes', 'origen')) {
                    $datosContraparte['origen'] = 'PLANIFICADO';
                }
                $informe->contrapartes()->create($datosContraparte);
            }
            $this->sincronizarInstrumentosContraparte($informe, $proyecto);

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
                    'origen_fondos' => 'registro_proyecto',
                ]);
            }
            $this->sincronizarDetalleAporteContraparte($informe);

            foreach ($proyecto->ods as $ods) {
                $metas = $proyecto->metasContribuye->where('ods_id', $ods->id);
                if ($metas->isEmpty()) {
                    $informe->ods()->create(['ods_id' => $ods->id, 'nivel_contribucion' => 'directa', 'origen' => 'PLANIFICADO']);
                } else {
                    foreach ($metas as $meta) {
                        $informe->ods()->create([
                            'ods_id' => $ods->id,
                            'meta_contribuye_id' => $meta->id,
                            'meta_ods' => trim('Meta '.$meta->numero_meta.': '.$meta->descripcion),
                            'nivel_contribucion' => 'directa',
                            'origen' => 'PLANIFICADO',
                        ]);
                    }
                }
            }

            return $informe->load($this->relations());
        }, 3);
    }

    private function relations(): array
    {
        return ['proyecto', 'beneficiarios', 'equipoDocente', 'cooperacion', 'gruposEstudiantes.asignatura', 'gruposEstudiantes.estudiantes', 'estudiantes.grupo.asignatura', 'voluntarios', 'contrapartes', 'resultados', 'actividades.participantes', 'accionesNoEjecutadas', 'accionesEmergentes', 'ods.ods', 'ods.meta', 'presupuestoDetalles', 'anexos'];
    }

    private function completarSnapshotsFaltantes(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        if ($informe->estado !== 'BORRADOR') {
            if (! $informe->gruposEstudiantes()->exists()) {
                $this->sincronizarGruposEstudiantes($informe, $proyecto);
            }
            if (! $informe->anexos()->where('categoria', 'instrumento_contraparte')->exists()) {
                $this->sincronizarInstrumentosContraparte($informe, $proyecto);
            }
            $this->sincronizarEstudiantesConGrupos($informe, $proyecto);
            return;
        }

        // Corrige únicamente el valor heredado que expuso un ID interno.
        if (preg_match('/^Proyecto #\d+$/', trim((string) $informe->numero_registro))) {
            $informe->update(['numero_registro' => $this->numeroRegistroOficial($proyecto)]);
        }

        $this->sincronizarGruposEstudiantes($informe, $proyecto);
        $this->sincronizarEstudiantesConGrupos($informe, $proyecto);
        $this->sincronizarRolesEquipo($informe, $proyecto);
        $this->sincronizarAporteMonetarioContraparte($informe, $proyecto);
        $this->sincronizarDetalleAporteContraparte($informe);
        $this->sincronizarInstrumentosContraparte($informe, $proyecto);
        $informe->load(['estudiantes', 'voluntarios', 'actividades.participantes']);

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

    private function sincronizarGruposEstudiantes(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        $idsPlanificacion = [];

        foreach ($proyecto->gruposEstudiantesPlanificados as $registro) {
            $tipo = ParticipacionEstudiantil::normalizar($registro->tipo_participacion_estudiante);
            $grupo = $informe->gruposEstudiantes()->updateOrCreate(
                ['estudiante_proyecto_id' => $registro->id],
                [
                    'tipo_participacion' => $tipo,
                    'asignatura_id' => $registro->asignatura_id,
                    'periodo_academico' => $registro->periodo_academico_id,
                    'hombres_planificados' => max(0, (int) $registro->cantidad_estudiantes_hombres),
                    'mujeres_planificadas' => max(0, (int) $registro->cantidad_estudiantes_mujeres),
                ]
            );
            $idsPlanificacion[] = $registro->id;

            $estudiante = $registro->estudiante;
            if ($estudiante) {
                $informe->estudiantes()->firstOrCreate(
                    ['informe_final_grupo_estudiante_id' => $grupo->id, 'estudiante_id' => $estudiante->id],
                    [
                        'nombre' => trim($estudiante->nombre.' '.$estudiante->apellido),
                        'sexo' => $estudiante->sexo,
                        'numero_cuenta' => $estudiante->cuenta,
                        'carrera' => $estudiante->carrera?->nombre ?: $registro->carrera?->nombre,
                        'correo' => $estudiante->user?->email,
                        'tipo_participacion' => $tipo,
                        'cantidad' => 1,
                        'origen' => 'PROYECTO',
                    ]
                );
            }
        }

        $obsoletos = $informe->gruposEstudiantes()
            ->when($idsPlanificacion, fn ($query) => $query->whereNotIn('estudiante_proyecto_id', $idsPlanificacion))
            ->when(! $idsPlanificacion, fn ($query) => $query)
            ->withCount('estudiantes')
            ->get();
        foreach ($obsoletos as $grupo) {
            if ($grupo->estudiantes_count === 0) {
                $grupo->delete();
            }
        }
    }

    private function crearParticipantesActividad($snapshot, $empleados): void
    {
        foreach ($empleados->unique('id')->values() as $orden => $empleado) {
            $snapshot->participantes()->firstOrCreate(
                ['tipo' => 'docente', 'empleado_id' => $empleado->id],
                ['nombre' => $empleado->nombre_completo, 'rol' => $orden === 0 ? 'Responsable principal' : 'Participante', 'es_responsable' => $orden === 0, 'orden' => $orden, 'origen' => 'PLANIFICADO', 'estado_participacion' => 'activo']
            );
        }
    }

    private function sincronizarEstudiantesConGrupos(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        $grupos = $informe->gruposEstudiantes()->get();
        foreach ($informe->estudiantes()->get() as $estudiante) {
            $grupoActual = $grupos->first(fn ($grupo) => (int) $grupo->id === (int) $estudiante->informe_final_grupo_estudiante_id);
            if ($grupoActual) {
                if ($estudiante->tipo_participacion !== $grupoActual->tipo_participacion) {
                    $estudiante->update(['tipo_participacion' => $grupoActual->tipo_participacion]);
                }
                continue;
            }

            $tipo = ParticipacionEstudiantil::normalizar($estudiante->tipo_participacion) ?: 'voluntariado';
            $grupo = null;
            if ($estudiante->estudiante_id) {
                $planificacion = $proyecto->gruposEstudiantesPlanificados
                    ->first(fn ($registro) => (int) $registro->estudiante_id === (int) $estudiante->estudiante_id
                        && ParticipacionEstudiantil::normalizar($registro->tipo_participacion_estudiante) === $tipo);
                if ($planificacion) $grupo = $grupos->firstWhere('estudiante_proyecto_id', $planificacion->id);
            }
            $coincidentes = $grupos->where('tipo_participacion', $tipo);
            if (! $grupo && $coincidentes->count() === 1) $grupo = $coincidentes->first();
            if (! $grupo) {
                $grupo = $informe->gruposEstudiantes()->firstOrCreate(
                    ['estudiante_proyecto_id' => null, 'tipo_participacion' => $tipo],
                    ['hombres_planificados' => 0, 'mujeres_planificadas' => 0]
                );
                $grupos->push($grupo);
            }
            $estudiante->update(['informe_final_grupo_estudiante_id' => $grupo->id, 'tipo_participacion' => $grupo->tipo_participacion]);
        }
    }

    private function sincronizarRolesEquipo(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        foreach ($proyecto->docentes_proyecto as $miembro) {
            $snapshot = $informe->equipoDocente()->where('empleado_id', $miembro->empleado_id)->first();
            if (! $snapshot) continue;

            $rol = trim((string) $miembro->rol);
            $esCoordinador = strcasecmp($rol, 'Coordinador') === 0;
            $snapshot->update([
                'tipo_participacion' => $rol ?: ($snapshot->es_coordinador ? 'Coordinador' : 'Integrante'),
                'es_coordinador' => $esCoordinador || ($rol === '' && $snapshot->es_coordinador),
            ]);
        }
    }

    private function sincronizarInstrumentosContraparte(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        foreach ($proyecto->entidad_contraparte_proyecto()->with('entidadContraparte')->with('instrumentoFormalizacion')->get() as $pivot) {
            $catalogo = $pivot->entidadContraparte;
            $contraparte = $informe->contrapartes->firstWhere('entidad_contraparte_id', $catalogo->id)
                ?: $informe->contrapartes()->where('entidad_contraparte_id', $catalogo->id)->first();
            if (! $contraparte) continue;

            foreach ($pivot->instrumentoFormalizacion as $instrumento) {
                $informe->anexos()->updateOrCreate(
                    ['instrumento_formalizacion_id' => $instrumento->id],
                    [
                        'informe_final_contraparte_id' => $contraparte->id,
                        'tipo' => 'otros',
                        'categoria' => 'instrumento_contraparte',
                        'descripcion' => $instrumento->tipo_documento_display,
                        'archivo' => $instrumento->documento_url,
                        'nombre_archivo' => $instrumento->nombre_archivo ?: basename((string) $instrumento->documento_url),
                        'fecha' => $instrumento->created_at?->toDateString(),
                        'origen' => 'PROYECTO',
                    ]
                );
            }
        }
    }

    private function sincronizarAporteMonetarioContraparte(InformeFinalProyecto $informe, Proyecto $proyecto): void
    {
        $aporte = (float) optional($proyecto->presupuesto)->aporte_contraparte;
        $contrapartes = $informe->contrapartes()->get();

        if ($aporte <= 0 || $contrapartes->count() !== 1) {
            return;
        }

        $contraparte = $contrapartes->first();
        if ((float) $contraparte->aporte_monetario === 0.0) {
            $contraparte->update(['aporte_monetario' => $aporte]);
        }
    }

    /** La fila agregada al presupuesto es la única fuente del subtotal de contraparte. */
    private function sincronizarDetalleAporteContraparte(InformeFinalProyecto $informe): void
    {
        $total = round($informe->contrapartes()
            ->where('existe_apoyo', true)
            ->get()
            ->sum(fn ($contraparte) => (float) $contraparte->aporte_monetario + (float) $contraparte->aporte_especie), 2);

        if (! $informe->contrapartes()->exists()) {
            return;
        }

        $informe->presupuestoDetalles()->updateOrCreate(
            ['origen_fondos' => 'contrapartes_proyecto'],
            [
                'fuente' => 'CONTRAPARTE',
                'concepto' => 'Aporte contraparte',
                'unidad' => 'aporte',
                'cantidad' => 1,
                'costo_unitario' => $total,
            ]
        );
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
