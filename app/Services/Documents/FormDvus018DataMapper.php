<?php

namespace App\Services\Documents;

use App\Models\ENF\EnfAccion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FormDvus018DataMapper
{
    /** @return array<int, array{0:int,1:int,2:int,3:string,4?:bool}> */
    public function cells(EnfAccion $action): array
    {
        $place = $action->lugaresEjecucion->first();
        $beneficiaries = $action->beneficiarios;
        $catalogs = $action->accionCatalogos
            ->groupBy(fn ($item) => $item->tipo ?: ($item->catalogo?->tipo ?? ''))
            ->map(fn ($items) => $items->flatMap(fn ($item) => [$item->catalogo?->nombre, $item->valor_texto])->filter()->values());
        $team = $action->equipo->groupBy('rol');
        $counterpart = $action->contrapartes->first();
        $requestedAt = $action->fecha_solicitud ?? $action->created_at;
        $cells = [];
        $put = function (int $table, int $row, int $cell, mixed $value, bool $noWrap = false) use (&$cells): void {
            $cells[] = [$table, $row, $cell, trim((string) ($value ?? '')), $noWrap];
        };

        $put(1, 2, 2, $requestedAt?->format('Y'), true);
        $put(1, 2, 3, $requestedAt?->format('m'), true);
        $put(1, 2, 4, $requestedAt?->format('d'), true);
        $put(1, 3, 2, $action->nombre_accion);
        foreach ([['Proyecto de educación continua', 5, 2], ['Diplomado', 5, 3], ['Congreso', 7, 2], ['Seminario', 7, 3]] as [$needle, $row, $cell]) {
            $put(1, $row, $cell, $this->marked($catalogs, 'tipo_accion_enf', $needle));
        }
        $put(1, 10, 2, $action->resolucion_original ?: $action->resolucion_vra, true);
        $put(1, 10, 3, $action->resolucion_actualizacion, true);
        $put(1, 11, 3, $action->centroFacultad?->nombre ?? $action->unidad_academica_responsable_texto);
        $put(1, 12, 3, $action->departamentoAcademico?->nombre ?? $action->escuela_departamento_texto);
        $put(1, 13, 3, $action->carrera?->nombre);
        $put(1, 14, 2, $action->numero_edicion, true);
        foreach ([[2, 'd'], [3, 'm'], [4, 'Y']] as [$cell, $format]) {
            $put(1, 17, $cell, $action->fecha_inicio?->format($format), true);
        }
        foreach ([[5, 'd'], [6, 'm'], [7, 'Y']] as [$cell, $format]) {
            $put(1, 17, $cell, $action->fecha_finalizacion?->format($format), true);
        }
        $modality = $this->normalized($place?->modalidad_ejecucion ?: $action->modalidad?->nombre);
        $put(1, 19, 2, str_contains($modality, 'presencial') && ! str_contains($modality, 'semi') ? 'X' : '');
        $put(1, 19, 3, str_contains($modality, 'semi') ? 'X' : '');
        $put(1, 19, 4, str_contains($modality, 'virtual') && ! str_contains($modality, 'semi') && ! str_contains($modality, 'sincron') && ! str_contains($modality, 'teledocencia') ? 'X' : '');
        $put(1, 19, 5, str_contains($modality, 'sincron') || str_contains($modality, 'teledocencia') ? 'X' : '');
        $put(1, 21, 2, $action->horas_teoricas, true);
        $put(1, 21, 3, $action->horas_practicas, true);
        $put(1, 21, 4, $action->total_horas, true);
        $put(1, 22, 3, $place?->campus?->nombre_campus ?? $place?->nombre_lugar);
        $put(1, 23, 3, $place?->aula);
        $put(1, 24, 3, $place?->edificio);
        $platformText = $place?->descripcion_plataformas ?: ($place?->direccion ?? '');
        $put(1, 25, 1, 'Descripción de las plataformas que se utilizarán para la modalidad virtual y tele docencia (en los casos que aplique)'.($platformText ? "\n{$platformText}" : ''));
        foreach ([26 => ['Teams', 'Zoom', 'Meet', 'Webex', 'Otro'], 28 => ['Campus Virtual UNAH', 'Moodle', 'Classroom Google', 'Teams', 'Otro']] as $labelRow => $options) {
            $type = $labelRow === 26 ? 'plataforma_teledocencia' : 'plataforma_campus_virtual';
            foreach ($options as $index => $option) {
                $isMarked = $this->marked($catalogs, $type, $option) || (! $catalogs->has($type) && str_contains($this->normalized($platformText), $this->normalized($option)));
                $put(1, $labelRow + 1, $index + 2, $isMarked ? 'X' : '');
            }
        }
        $antecedents = ['Iniciativa de la unidad académica', 'Solicitud externa privada', 'Solicitud de Secretaría de Estado', 'Solicitud de gobierno local', 'Alianza con otras universidades', 'Solicitud de ONG', 'Solicitud de patronatos', 'Solicitud de sector financiero', 'Solicitud de sector productivo', 'Otros'];
        foreach ($antecedents as $index => $option) {
            $put(1, $index < 5 ? 32 : 34, ($index % 5) + 1, $this->marked($catalogs, 'antecedente', $option));
        }

        $profiles = ['Egresados UNAH', 'Funcionarios públicos', 'Estudiantes universitarios', 'Empresa privada de servicios', 'Sociedad civil', 'Líderes comunitarios', 'ONG', 'Profesionales universitarios otros CES', 'Sector productivo', 'Académicos'];
        foreach ($profiles as $index => $option) {
            $put(2, $index < 5 ? 3 : 5, ($index % 5) + 1, $this->marked($catalogs, 'perfil_participante', $option));
        }
        $put(2, 7, 2, $beneficiaries?->total, true);
        foreach (['14-18', '19-25', '26-40', '41-55', '56-70', 'Mayores de 70'] as $index => $option) {
            $put(2, $index < 3 ? 9 : 11, ($index % 3) + 2, $this->marked($catalogs, 'rango_edad', $option));
        }
        foreach (['Mestizos', 'Grupos étnicos', 'Población vulnerable', 'Personas con discapacidad', 'Desplazados por violencia', 'Otro'] as $index => $option) {
            $put(2, $index < 3 ? 13 : 15, ($index % 3) + 2, $this->marked($catalogs, 'condicion_social', $option));
        }

        $this->putLeader($put, 2, $team->get('Coordinador de la accion', collect())->first());
        $this->putLeader($put, 5, $team->get('Responsable de sistematizacion', collect())->first());
        $this->putMembers($put, 10, $team->get('Docente UNAH', collect()), ['numero_empleado', 'correo', 'categoria', 'departamento', 'jornada_laboral']);
        $this->putMembers($put, 17, $team->get('Consultor nacional', collect()), ['profesion', 'correo', 'horas_dedicadas']);
        $this->putMembers($put, 24, $team->get('Consultor internacional', collect()), ['nacionalidad', 'correo', 'horas_dedicadas']);

        $participation = $action->participacionUniversitaria->keyBy('tipo_participacion');
        $number = fn (string $type, string $gender = 'cantidad') => $this->participationValue($participation, $type, $gender);
        $put(4, 3, 2, $number('Estudiantes de grado / posgrado', 'hombres'), true);
        $put(4, 5, 2, $number('Estudiantes de grado / posgrado', 'mujeres'), true);
        foreach ([['Práctica de asignatura', 3], ['Práctica de asignatura', 4], ['Servicio Social o PPS', 5], ['Servicio Social o PPS', 6], ['Voluntariado', 7], ['Voluntariado', 8]] as $index => [$type, $cell]) {
            $put(4, 5, $cell, $number($type, $index % 2 === 0 ? 'hombres' : 'mujeres'), true);
        }
        $put(4, 8, 2, $number('Personal docente', 'hombres'), true);
        $put(4, 10, 2, $number('Personal docente', 'mujeres'), true);
        foreach ([['Profesores x hora', 3], ['Profesores x hora', 4], ['Profesores horarios', 5], ['Profesores horarios', 6], ['Profesores permanentes', 7], ['Profesores permanentes', 8]] as $index => [$type, $cell]) {
            $put(4, 10, $cell, $number($type, $index % 2 === 0 ? 'hombres' : 'mujeres'), true);
        }
        $put(4, 15, 2, $number('Personal administrativo', 'hombres'), true);
        $put(4, 17, 2, $number('Personal administrativo', 'mujeres'), true);
        foreach ([['Administrativo', 3], ['Administrativo', 4], ['Servicios', 5], ['Servicios', 6], ['Asistentes técnicos laboratorios / instructores', 7], ['Asistentes técnicos laboratorios / instructores', 8]] as $index => [$type, $cell]) {
            $put(4, 17, $cell, $number($type, $index % 2 === 0 ? 'hombres' : 'mujeres'), true);
        }
        foreach ($this->fixedRows($action->practicasAsignatura, 3) as $index => $practice) {
            $row = 20 + $index;
            $put(4, $row, 1, $practice?->codigo_asignatura, true);
            $put(4, $row, 2, $practice?->asignatura?->nombre ?? $practice?->nombre_asignatura);
            $put(4, $row, 3, $practice?->periodoAcademico?->nombre ?? $practice?->periodo_academico_texto);
            $put(4, $row, 4, $practice?->matricula_hombres, true);
            $put(4, $row, 5, $practice?->matricula_mujeres, true);
        }

        $put(5, 2, 2, $counterpart ? 'X' : '');
        $put(5, 2, 3, $counterpart ? '' : 'X');
        $counterpartType = $counterpart?->tipoContraparte?->nombre;
        foreach (['Secretaría de Estado', 'Gobierno Municipal', 'Sector productivo', 'Entidades financieras', 'Sector privado de servicios', 'Organizaciones gremiales', 'Sociedad civil organizada', 'Sector académico', 'Organismos internacionales', 'Unidad de la UNAH'] as $index => $option) {
            $put(5, $index < 5 ? 5 : 7, ($index % 5) + 1, $this->contains($counterpartType, $option) ? 'X' : '');
        }
        $put(5, 8, 2, trim(($counterpart?->nombre ?? '').($counterpart?->rtn ? " — RTN: {$counterpart->rtn}" : '')));
        $put(5, 9, 2, $counterpart?->representante);
        $put(5, 10, 2, $counterpart?->cargo_contacto);
        $put(5, 12, 2, $counterpart?->correo);
        $put(5, 12, 3, $counterpart?->telefono, true);
        $put(5, 13, 2, $counterpart?->direccion);
        foreach ([['Carta formal', 2], ['Carta de intenciones', 3], ['Convenio marco', 4]] as [$option, $cell]) {
            $put(5, 15, $cell, $this->contains($counterpart?->instrumentoAlianza?->nombre, $option) ? 'X' : '');
        }
        $put(5, 16, 2, $counterpart?->compromisos);

        $put(6, 2, 1, $action->resumen);
        $put(6, 4, 1, $action->definicion_problema);
        $put(6, 6, 1, $action->objetivo_general);
        $put(6, 8, 1, $action->objetivosEspecificos->sortBy('orden')->values()->map(fn ($objective, $index) => ($index + 1).'. '.trim($objective->descripcion))->implode("\n"));
        $results = $action->resultados->sortBy('orden')->groupBy(fn ($result) => $this->resultType($result->resultado));
        $this->putResults($put, $results->get('corto', collect()), 12, 6, true, $action);
        $this->putResults($put, $results->get('mediano', collect()), 20, 5, false, $action);
        $this->putResults($put, $results->get('largo', collect()), 27, 5, false, $action);
        $selectedOds = $action->ods->filter(fn ($ods) => filled($ods?->id))->unique('id')->sortBy('id')->values();
        $metas = $action->metasContribuye->filter(fn ($meta) => filled($meta?->id))->unique('id')->groupBy('ods_id');
        foreach ($this->fixedRows($selectedOds, 4) as $index => $ods) {
            $put(6, 34 + $index, 1, $index === 0 ? $selectedOds->count() : '', true);
            $put(6, 34 + $index, 2, $ods?->nombre);
            $put(6, 34 + $index, 3, $ods ? $metas->get($ods->id, collect())->map(fn ($meta) => trim(($meta->numero_meta ? $meta->numero_meta.'. ' : '').($meta->descripcion ?? $meta->nombre ?? '')))->implode("\n") : '');
        }
        $put(6, 39, 1, $action->alineamiento_reforma);
        $put(6, 41, 1, $action->logistica);

        $income = $action->presupuestos->firstWhere('tipo', 'ingresos');
        $expenses = $action->presupuestos->firstWhere('tipo', 'egresos');
        $unah = $action->presupuestos->firstWhere('tipo', 'aporte_unah');
        $put(7, 2, 2, $action->genera_ingresos ? 'X' : '');
        $put(7, 2, 3, $action->genera_ingresos ? '' : 'X');
        $this->putBudget($put, $income, 5, ['Cuotas de inscripción', 'Mensualidades / módulos', 'Gestión de becas', 'Otros']);
        $put(7, 9, 2, $this->money($income?->detalles?->sum('total')));
        $this->putBudget($put, $expenses, 12, ['Pago de personal docente', 'Gastos de materiales', 'Gastos de movilización', 'Gastos de manutención', 'Costos administrativos', 'Otros gastos']);
        $incomeTotal = (float) ($income?->detalles?->sum('total') ?? 0);
        $expenseTotal = (float) ($expenses?->detalles?->sum('total') ?? 0);
        $put(7, 18, 2, $this->money($expenseTotal));
        $put(7, 19, 2, $this->money($incomeTotal - $expenseTotal));
        $put(7, 20, 2, $action->descripcion_excedente);
        $put(7, 21, 3, $this->contains($action->mecanismo_administracion, 'FUNDAUNAH') ? 'X' : '');
        $put(7, 21, 5, $this->contains($action->mecanismo_administracion, 'Tesorer') ? 'X' : '');
        $this->putBudget($put, $unah, 3, ['Horas de participación del personal docente', 'Horas de participación estudiantes', 'Costos indirectos depreciación', 'Costos indirectos servicios públicos'], 8);
        $put(8, 7, 2, $this->money($unah?->detalles?->sum('total')));

        foreach ($this->fixedRows($action->cronograma, 9) as $index => $item) {
            $row = 4 + $index;
            $put(9, $row, 1, $item?->actividad);
            $put(9, $row, 2, $item?->producto);
            $put(9, $row, 3, $item?->fecha_inicio?->format('d/m/Y'), true);
            $put(9, $row, 4, $item?->responsable_texto);
            $put(9, $row, 5, $item?->horas_requeridas, true);
        }
        $put(10, 3, 1, $this->signature($action, ['Coordinador de la acción por la UNAH'], $this->memberName($team->get('Coordinador de la accion', collect())->first())));
        $put(10, 3, 2, $this->signature($action, ['Jefe de la Unidad Académica que lidera la acción']));
        $put(11, 3, 1, $this->signature($action, ['Coordinador(a) del Comité Local', 'Coordinador del Comité Local']));
        $put(11, 3, 2, $this->signature($action, ['Decano(a) o Director(a) del Centro Regional']));
        foreach (['Oficio de remisión', 'Documento perfil', 'Otros'] as $index => $needle) {
            $exists = $action->documentos->contains(fn ($document) => $this->contains($document->nombre, $needle));
            $put(12, 2 + $index, 3, $exists ? 'X' : '');
            $put(12, 2 + $index, 4, $exists ? '' : 'X');
        }

        return $cells;
    }

    private function putLeader(callable $put, int $startRow, mixed $member): void
    {
        $put(3, $startRow, 2, 'Nombre Completo: '.$this->memberName($member));
        $put(3, $startRow, 3, 'No. de empleado/a: '.($member?->numero_empleado ?? ''), true);
        $put(3, $startRow + 1, 2, 'Correo electrónico: '.($member?->correo ?? ''));
        $put(3, $startRow + 1, 3, 'Celular: '.($member?->celular ?? ''), true);
        $put(3, $startRow + 2, 2, 'Categoría: '.($member?->categoria ?? ''));
        $put(3, $startRow + 2, 3, 'Departamento al que pertenece: '.($member?->departamento ?? ''));
    }

    private function putMembers(callable $put, int $startRow, Collection $members, array $fields): void
    {
        foreach ($this->fixedRows($members, 5) as $index => $member) {
            $row = $startRow + $index;
            $put(3, $row, 1, $index + 1, true);
            $put(3, $row, 2, $this->memberName($member));
            foreach ($fields as $fieldIndex => $field) {
                $put(3, $row, $fieldIndex + 3, $member?->{$field}, in_array($field, ['numero_empleado', 'horas_dedicadas'], true));
            }
        }
    }

    private function putResults(callable $put, Collection $results, int $startRow, int $capacity, bool $withObjective, EnfAccion $action): void
    {
        foreach ($this->fixedRows($results, $capacity) as $index => $result) {
            $row = $startRow + $index;
            $description = $result ? preg_replace('/^[^:]+:\s*/u', '', (string) $result->resultado) : '';
            if ($withObjective) {
                $objective = $result ? $action->objetivosEspecificos->firstWhere('id', $result->enf_objetivo_especifico_id) : null;
                $put(6, $row, 1, $objective?->orden, true);
                $put(6, $row, 2, $description);
                $put(6, $row, 3, $result?->indicador ?: $result?->medio_verificacion);
            } else {
                $put(6, $row, 1, $description);
                $put(6, $row, 2, $result?->indicador ?: $result?->medio_verificacion);
            }
        }
    }

    private function putBudget(callable $put, mixed $budget, int $startRow, array $needles, int $table = 7): void
    {
        foreach ($needles as $index => $needle) {
            $detail = collect($budget?->detalles ?? [])->first(fn ($item) => $this->contains($item->rubro, $needle));
            $row = $startRow + $index;
            $put($table, $row, 2, $detail?->cantidad, true);
            $put($table, $row, 3, $detail ? $this->money($detail->costo_unitario) : '', true);
            $put($table, $row, 4, $detail ? $this->money($detail->total) : '', true);
        }
    }

    private function fixedRows(iterable $items, int $capacity): Collection
    {
        $rows = collect($items)->values()->take($capacity);
        while ($rows->count() < $capacity) {
            $rows->push(null);
        }

        return $rows;
    }

    private function marked(Collection $catalogs, string $type, string $needle): string
    {
        return ($catalogs[$type] ?? collect())->contains(fn ($value) => $this->contains($value, $needle)) ? 'X' : '';
    }

    private function contains(mixed $value, mixed $needle): bool
    {
        return str_contains($this->normalized($value), $this->normalized($needle));
    }

    private function normalized(mixed $value): string
    {
        return Str::of(Str::ascii((string) $value))->lower()->squish()->toString();
    }

    private function memberName(mixed $member): string
    {
        return $member ? ($member->nombre_completo ?: ($member->empleado?->nombre_completo ?? '')) : '';
    }

    private function participationValue(Collection $participation, string $type, string $field): mixed
    {
        $item = $participation->get($type);
        if (! $item) {
            return '';
        }
        if (in_array($field, ['hombres', 'mujeres'], true) && preg_match('/'.ucfirst($field).':\s*(\d+)/iu', (string) $item->descripcion, $match)) {
            return $match[1];
        }

        return $item->{$field} ?? '';
    }

    private function resultType(mixed $result): string
    {
        $type = $this->normalized(str($result)->before(':'));

        return str_contains($type, 'mediano') ? 'mediano' : ((str_contains($type, 'largo') || str_contains($type, 'impacto')) ? 'largo' : 'corto');
    }

    private function signature(EnfAccion $action, array $needles, string $fallback = ''): string
    {
        $signature = $action->firmas->first(fn ($item) => collect($needles)->contains(fn ($needle) => $this->contains($item->rol_firma, $needle)));

        return $signature?->nombre_firmante ?: ($signature?->empleado?->nombre_completo ?? $fallback);
    }

    private function money(mixed $value): string
    {
        return 'L '.number_format((float) ($value ?? 0), 2, '.', ',');
    }
}
