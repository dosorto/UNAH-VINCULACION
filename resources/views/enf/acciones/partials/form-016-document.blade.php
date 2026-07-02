@php
    $assetUrl = fn (string $path) => asset($path);
    $headerUrl = $assetUrl('images/enf/form-018-header.png');
    $certificado = $accion->certificado;
    $lugar = $accion->lugaresEjecucion->first();
    $beneficiarios = $accion->beneficiarios;
    $contraparte = $accion->contrapartes->first();
    $coordinador = $accion->equipo->firstWhere('rol', 'Coordinador de la accion');
    $docentes = $accion->equipo
        ->whereIn('rol', ['Docente UNAH', 'Consultor nacional', 'Consultor internacional'])
        ->values();
    $catalogosPorTipo = $accion->accionCatalogos->groupBy('tipo');
    $presupuestosPorTipo = $accion->presupuestos->keyBy('tipo');
    $firmasPorRol = $accion->firmas->keyBy('rol_firma');

    $value = fn ($value, string $fallback = '') => filled($value) ? $value : $fallback;
    $date = fn ($value) => filled($value) ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '';
    $time = fn ($value) => filled($value) ? substr((string) $value, 0, 5) : '';
    $money = fn ($value) => number_format((float) $value, 2);
    $checkbox = fn (bool $checked) => $checked ? '☒' : '☐';
    $catalogNames = function (string $tipo) use ($catalogosPorTipo) {
        return $catalogosPorTipo->get($tipo, collect())
            ->map(fn ($item) => $item->catalogo?->nombre)
            ->filter()
            ->values();
    };
    $hasCatalog = function (string $tipo, string $needle) use ($catalogNames) {
        return $catalogNames($tipo)
            ->contains(fn ($name) => str($name)->ascii()->lower()->contains(str($needle)->ascii()->lower()));
    };
    $budgetRows = function (string $tipo, array $defaults) use ($presupuestosPorTipo) {
        $detalles = $presupuestosPorTipo->get($tipo)?->detalles ?? collect();

        return collect($defaults)->map(function ($rubro, $index) use ($detalles) {
            $detalle = $detalles->first(fn ($item) => str($item->rubro)->lower()->contains(str($rubro)->lower()))
                ?? $detalles->values()->get($index);

            return [
                'rubro' => $detalle?->rubro ?: $rubro,
                'cantidad' => $detalle?->cantidad,
                'costo_unitario' => $detalle?->costo_unitario,
                'total' => $detalle?->total,
            ];
        });
    };
    $days = collect((array) ($certificado?->dias_imparticion ?? []))->map(fn ($day) => (string) $day);
@endphp

<style>
    .form016-sheet {
        width: 8.5in;
        max-width: 100%;
        margin: 0 auto;
        background: #fff;
        color: #111827;
        font-family: "Arial Narrow", Arial, sans-serif;
        font-size: 10px;
        line-height: 1.2;
        padding: 0.35in;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
    }

    .form016-header {
        width: 100%;
        margin-bottom: 10px;
    }

    .form016-code {
        background: #002060;
        color: #fff;
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: 700;
        padding: 4px 8px;
        text-align: right;
    }

    .form016-title {
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: 700;
        margin: 6px 0 10px;
        text-align: right;
    }

    .form016-section {
        color: #002060;
        font-size: 12px;
        font-weight: 700;
        margin: 10px 0 4px;
    }

    .form016-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
        margin-bottom: 8px;
    }

    .form016-table th,
    .form016-table td {
        border: 1px solid #bfbfbf;
        padding: 4px 5px;
        vertical-align: top;
        min-height: 20px;
        word-break: break-word;
    }

    .form016-blue {
        background: #002060;
        color: #fff;
        font-weight: 700;
    }

    .form016-gray {
        background: #d9d9d9;
        font-weight: 700;
    }

    .form016-center {
        text-align: center;
    }

    .form016-right {
        text-align: right;
    }

    .form016-large {
        min-height: 58px;
    }

    .form016-signature {
        height: 56px;
    }

    @media print {
        body {
            background: #fff !important;
        }

        .form016-sheet {
            box-shadow: none;
            width: 100%;
            padding: 0;
        }
    }
</style>

<div class="form016-sheet">
    <img class="form016-header" src="{{ $headerUrl }}" alt="UNAH VRA Dirección de Vinculación Universidad Sociedad">
    <div class="form016-code">FORM-DVUS-016</div>
    <div class="form016-title">FORMULARIO DE REGISTRO DE CERTIFICADOS UNIVERSITARIOS / EDUCACION NO FORMAL</div>

    <div class="form016-section">• INFORMACION GENERAL DEL CERTIFICADO UNIVERSITARIO</div>
    <table class="form016-table">
        <colgroup>
            <col style="width: 28%">
            <col style="width: 24%">
            <col style="width: 16%">
            <col style="width: 16%">
            <col style="width: 16%">
        </colgroup>
        <tr>
            <td class="form016-blue" rowspan="2">• Fecha de solicitud de registro</td>
            <td class="form016-blue form016-center">Año</td>
            <td class="form016-blue form016-center">Mes</td>
            <td class="form016-blue form016-center" colspan="2">Dia</td>
        </tr>
        <tr>
            <td class="form016-center">{{ $accion->fecha_solicitud?->format('Y') }}</td>
            <td class="form016-center">{{ $accion->fecha_solicitud?->format('m') }}</td>
            <td class="form016-center" colspan="2">{{ $accion->fecha_solicitud?->format('d') }}</td>
        </tr>
        <tr>
            <td class="form016-blue">• Nombre completo del Certificado</td>
            <td colspan="4">{{ $value($certificado?->nombre_certificado, $accion->nombre_accion) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">• Codigo de Certificado<br><span>(Asignado por la DAFT)</span></td>
            <td colspan="4">{{ $value($certificado?->codigo_certificado) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">• Numero de edicion del certificado Universitario.</td>
            <td colspan="4">{{ $value($accion->numero_edicion) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">• Tipo de Certificado:</td>
            <td class="form016-gray">Basico</td>
            <td class="form016-center">{{ $checkbox(str($certificado?->tipoCertificado?->nombre)->ascii()->lower()->contains('basico')) }}</td>
            <td class="form016-gray">Avanzado</td>
            <td class="form016-center">{{ $checkbox(str($certificado?->tipoCertificado?->nombre)->ascii()->lower()->contains('avanzado')) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <colgroup>
            <col style="width: 34%">
            <col style="width: 36%">
            <col style="width: 30%">
        </colgroup>
        <tr>
            <td class="form016-blue">• Carreras aprobadas por Consejo Universitario</td>
            <td class="form016-gray form016-center">Nombre de las Carreras</td>
            <td class="form016-gray form016-center">No. Acuerdos de Consejo Universitario</td>
        </tr>
        @for ($i = 0; $i < 4; $i++)
            @php $carrera = $certificado?->carreras?->values()->get($i); @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $value($carrera?->nombre_carrera, $carrera?->carrera?->nombre) }}</td>
                <td>{{ $value($carrera?->acuerdo_consejo_universitario) }}</td>
            </tr>
        @endfor
    </table>

    <table class="form016-table">
        <tr>
            <td class="form016-blue" colspan="5">• Informacion general del Certificado Universitario</td>
        </tr>
        <tr>
            <td class="form016-gray form016-center" style="width: 8%">N°</td>
            <td class="form016-gray form016-center" style="width: 42%">Nombre asignatura</td>
            <td class="form016-gray form016-center" style="width: 18%">Codigo</td>
            <td class="form016-gray form016-center" style="width: 16%">No. de creditos</td>
            <td class="form016-gray form016-center" style="width: 16%">No. de horas</td>
        </tr>
        @for ($i = 0; $i < 6; $i++)
            @php $espacio = $accion->espaciosAprendizaje->values()->get($i); @endphp
            <tr>
                <td class="form016-center">{{ $i + 1 }}</td>
                <td>{{ $value($espacio?->nombre) }}</td>
                <td class="form016-center">{{ $value($espacio?->codigo) }}</td>
                <td class="form016-center">{{ $value($espacio?->creditos) }}</td>
                <td class="form016-center">{{ $value($espacio?->horas) }}</td>
            </tr>
        @endfor
    </table>

    <table class="form016-table">
        <tr>
            <td class="form016-blue" style="width: 35%">• Unidad academica responsable</td>
            <td>{{ $value($accion->unidad_academica_responsable_texto, $accion->centroFacultad?->nombre) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">Escuela, Departamento Academico.</td>
            <td>{{ $value($accion->escuela_departamento_texto, $accion->departamentoAcademico?->nombre) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <tr>
            <td class="form016-blue" rowspan="2" style="width: 35%">• Carga horaria en creditos academicos</td>
            <td class="form016-gray form016-center">Creditos academicos</td>
            <td class="form016-gray form016-center">Horas teoricas</td>
            <td class="form016-gray form016-center">Horas practicas</td>
            <td class="form016-gray form016-center">Total Horas</td>
        </tr>
        <tr>
            <td class="form016-center">{{ $value($accion->carga_horaria_creditos, 0) }}</td>
            <td class="form016-center">{{ $value($accion->horas_teoricas, 0) }}</td>
            <td class="form016-center">{{ $value($accion->horas_practicas, 0) }}</td>
            <td class="form016-center">{{ $value($accion->total_horas, 0) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">• Cupos Programados: (Maximo)</td>
            <td class="form016-gray form016-center">Mujeres</td>
            <td class="form016-center">{{ $value($beneficiarios?->mujeres, 0) }}</td>
            <td class="form016-gray form016-center">Hombres</td>
            <td class="form016-center">{{ $value($beneficiarios?->hombres, 0) }} / Total {{ $value($beneficiarios?->total, 0) }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <tr><td class="form016-blue" colspan="4">• Periodo de ejecucion</td></tr>
        <tr>
            <td class="form016-gray form016-center">Fecha de inicio</td>
            <td class="form016-gray form016-center">Fecha de finalizacion</td>
            <td class="form016-gray form016-center">Vigencia del Certificado</td>
            <td class="form016-gray form016-center">PAC / año</td>
        </tr>
        <tr>
            <td class="form016-center">{{ $date($accion->fecha_inicio) }}</td>
            <td class="form016-center">{{ $date($accion->fecha_finalizacion) }}</td>
            <td class="form016-center">{{ $value($certificado?->vigencia_certificado) }}</td>
            <td class="form016-center">{{ $value($certificado?->pac_certificado) }}</td>
        </tr>
        <tr>
            <td class="form016-gray form016-center" colspan="2">Fecha de emision maxima</td>
            <td class="form016-gray form016-center">Hora de inicio</td>
            <td class="form016-gray form016-center">Hora de finalizacion</td>
        </tr>
        <tr>
            <td class="form016-center" colspan="2">{{ $date($certificado?->fecha_emision_maxima) }}</td>
            <td class="form016-center">{{ $time($certificado?->hora_inicio) }}</td>
            <td class="form016-center">{{ $time($certificado?->hora_finalizacion) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Dias de imparticion</td>
            <td colspan="3">
                @foreach (['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'] as $dia)
                    <span style="display:inline-block;margin-right:14px">{{ $dia }} {{ $checkbox($days->contains($dia)) }}</span>
                @endforeach
            </td>
        </tr>
    </table>

    <table class="form016-table">
        <tr><td class="form016-blue" colspan="4">• Modalidad de ejecucion</td></tr>
        <tr>
            @foreach (['Presencial', 'Semi presencial', '100% virtual', 'Virtual sincronico'] as $modalidad)
                <td class="form016-center">{{ $modalidad }}<br>{{ $checkbox(str($lugar?->modalidad_ejecucion)->ascii()->lower()->contains(str($modalidad)->ascii()->lower()->replace('sincronico', 'sincronico'))) }}</td>
            @endforeach
        </tr>
        <tr>
            <td class="form016-gray">Lugar de imparticion</td>
            <td>{{ $value($lugar?->nombre_lugar) }}</td>
            <td class="form016-gray">No. Aula / Edificio / Centro</td>
            <td>{{ collect([$lugar?->aula, $lugar?->edificio, $lugar?->centro])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Descripcion de plataformas</td>
            <td colspan="3">{{ $value($lugar?->descripcion_plataformas) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Plataformas presencial</td>
            <td colspan="3">{{ $catalogNames('plataforma_presencial')->implode(', ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Plataformas a distancia</td>
            <td colspan="3">{{ $catalogNames('plataforma_distancia')->implode(', ') }}</td>
        </tr>
    </table>

    <table class="form016-table">
        <tr><td class="form016-blue" colspan="4">• Antecedentes de la accion</td></tr>
        @foreach (array_chunk(['Iniciativa de la unidad academica', 'Solicitud externa privada', 'Secretaria de Estado', 'Gobierno local', 'Universidades', 'ONG', 'Patronatos', 'Sector financiero', 'Sector productivo', 'Otros'], 2) as $row)
            <tr>
                @foreach ($row as $item)
                    <td>{{ $item }}</td>
                    <td class="form016-center">{{ $checkbox($hasCatalog('antecedente', $item)) }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <div class="form016-section">• PERFIL DE LOS BENEFICIARIOS (PARTICIPANTES)</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue" style="width: 35%">• Grado academico requerido</td>
            <td>
                @foreach (['Titulo de Educacion Media', 'Titulo Universitario', 'Acreditar experiencia comprobada en el area'] as $grado)
                    <span style="display:inline-block;margin-right:18px">{{ $grado }} {{ $checkbox($hasCatalog('grado_academico', $grado)) }}</span>
                @endforeach
            </td>
        </tr>
        <tr>
            <td class="form016-blue">• Perfil de los principales participantes</td>
            <td>{{ $catalogNames('perfil_participante')->implode(', ') }}{{ $accion->descripcion_participantes ? ' - '.$accion->descripcion_participantes : '' }}</td>
        </tr>
    </table>

    <div class="form016-section">• EQUIPO DOCENTE DEL CERTIFICADO</div>
    <table class="form016-table">
        <tr><td class="form016-blue" colspan="4">• Coordinador/a del Certificado Universitario</td></tr>
        <tr>
            <td class="form016-gray">Nombre completo</td>
            <td>{{ $value($coordinador?->nombre_completo) }}</td>
            <td class="form016-gray">No. de empleado</td>
            <td>{{ $value($coordinador?->numero_empleado) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Identidad</td>
            <td>{{ $value($coordinador?->identidad) }}</td>
            <td class="form016-gray">Correo / Celular</td>
            <td>{{ collect([$coordinador?->correo, $coordinador?->celular])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Categoria</td>
            <td>{{ $value($coordinador?->categoria) }}</td>
            <td class="form016-gray">Departamento</td>
            <td>{{ $value($coordinador?->departamento) }}</td>
        </tr>
    </table>

    @for ($i = 0; $i < max(2, $docentes->count()); $i++)
        @php $docente = $docentes->get($i); @endphp
        <table class="form016-table">
            <tr><td class="form016-blue" colspan="4">SECCION {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} - Datos del docente</td></tr>
            <tr>
                <td class="form016-gray">Perfil del docente</td>
                <td colspan="3">
                    Profesor UNAH {{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Profesor de la UNAH' || $docente?->rol === 'Docente UNAH') }}
                    &nbsp;&nbsp; Consultor Nacional {{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Consultor Nacional' || $docente?->rol === 'Consultor nacional') }}
                    &nbsp;&nbsp; Consultor Internacional {{ $checkbox(($docente?->perfil_docente ?: $docente?->rol) === 'Consultor Internacional' || $docente?->rol === 'Consultor internacional') }}
                </td>
            </tr>
            <tr>
                <td class="form016-gray">Nombre completo</td>
                <td>{{ $value($docente?->nombre_completo) }}</td>
                <td class="form016-gray">Espacio de aprendizaje</td>
                <td>{{ $value($docente?->espacio_aprendizaje) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">No. empleado / identidad</td>
                <td>{{ collect([$docente?->numero_empleado, $docente?->identidad])->filter()->implode(' / ') }}</td>
                <td class="form016-gray">Correo</td>
                <td>{{ $value($docente?->correo) }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Categoria / Departamento</td>
                <td>{{ collect([$docente?->categoria, $docente?->departamento])->filter()->implode(' / ') }}</td>
                <td class="form016-gray">Titulo / pais / universidad</td>
                <td>{{ collect([$docente?->ultimo_titulo, $docente?->pais_procedencia, $docente?->universidad_procedencia])->filter()->implode(' / ') }}</td>
            </tr>
            <tr>
                <td class="form016-gray">Asignacion academica</td>
                <td colspan="3">Carga academica del PAC {{ $checkbox((bool) $docente?->carga_academica_pac) }} &nbsp;&nbsp; Contratacion jornada contraria {{ $checkbox((bool) $docente?->contratacion_jornada_contraria) }}</td>
            </tr>
        </table>
    @endfor

    <div class="form016-section">• INFORMACION DE LA ENTIDAD CONTRAPARTE</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue">• LA ACTIVIDAD TIENE CONTRAPARTE</td>
            <td>SI {{ $checkbox((bool) $contraparte) }}</td>
            <td>NO {{ $checkbox(! $contraparte) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Perfil de la entidad contraparte</td>
            <td colspan="2">{{ $contraparte?->tipoContraparte?->nombre }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Nombre de la contraparte</td>
            <td colspan="2">{{ $value($contraparte?->nombre) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Contacto / cargo</td>
            <td colspan="2">{{ collect([$contraparte?->representante, $contraparte?->cargo_contacto])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Correo / telefono</td>
            <td colspan="2">{{ collect([$contraparte?->correo, $contraparte?->telefono])->filter()->implode(' / ') }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Direccion exacta</td>
            <td colspan="2">{{ $value($contraparte?->direccion) }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Instrumento de alianza</td>
            <td colspan="2">{{ $contraparte?->instrumentoAlianza?->nombre }}</td>
        </tr>
        <tr>
            <td class="form016-gray">Compromisos asumidos</td>
            <td colspan="2" class="form016-large">{{ $value($contraparte?->compromisos) }}</td>
        </tr>
    </table>

    <div class="form016-section">• INFORMACION ACADEMICA DEL CERTIFICADO</div>
    <table class="form016-table">
        <tr><td class="form016-blue">20.1 Resultados de Aprendizaje</td></tr>
        <tr><td class="form016-large">{{ $value($accion->resumen) }}</td></tr>
        <tr><td class="form016-blue">20.2 Impacto esperado</td></tr>
        <tr><td class="form016-large">{{ $value($accion->impacto_esperado) }}</td></tr>
        <tr><td class="form016-blue">20.3 Resumen de la logistica</td></tr>
        <tr><td class="form016-large">{{ $value($accion->logistica) }}</td></tr>
        <tr><td class="form016-blue">Requisitos de emision del certificado</td></tr>
        <tr><td>{{ $value($certificado?->requisitos_emision) }}</td></tr>
    </table>

    <div class="form016-section">• DETALLE DEL PRESUPUESTO</div>
    <table class="form016-table">
        <tr>
            <td class="form016-blue">Obtendra ingresos por la actividad</td>
            <td>SI {{ $checkbox((bool) $accion->genera_ingresos) }}</td>
            <td>NO {{ $checkbox(! $accion->genera_ingresos) }}</td>
        </tr>
    </table>

    @foreach ([
        'ingresos' => ['Presupuesto de ingresos', ['Cuotas de inscripción', 'Gestión de becas', 'Otros']],
        'egresos' => ['Presupuesto de egresos', ['Pago de conferencistas / facilitadores', 'Gastos de materiales y suministros', 'Gastos de movilización', 'Gastos de manutención y hospedaje', 'Costos administrativos / Financieros', 'Otros']],
        'aporte_unah' => ['Aporte de la UNAH', ['Personal docente', 'Horas de participación de los estudiantes', 'Horas de participación de voluntarios', 'Útiles y materiales de oficina', 'Costos indirectos depreciación de equipo', 'Costos indirectos servicios públicos']],
    ] as $tipo => [$titulo, $rubros])
        @php $rows = $budgetRows($tipo, $rubros); @endphp
        <table class="form016-table">
            <tr><td class="form016-blue" colspan="4">• {{ $titulo }} (manifestado en lempiras)</td></tr>
            <tr>
                <td class="form016-gray">Concepto</td>
                <td class="form016-gray form016-center">Cantidad</td>
                <td class="form016-gray form016-center">Costo unitario</td>
                <td class="form016-gray form016-center">Costo Total</td>
            </tr>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['rubro'] }}</td>
                    <td class="form016-center">{{ filled($row['cantidad']) ? $money($row['cantidad']) : '' }}</td>
                    <td class="form016-center">{{ filled($row['costo_unitario']) ? $money($row['costo_unitario']) : '' }}</td>
                    <td class="form016-center">{{ filled($row['total']) ? $money($row['total']) : '' }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="form016-right form016-gray" colspan="3">Total {{ $titulo }}</td>
                <td class="form016-center">{{ $money($presupuestosPorTipo->get($tipo)?->monto_solicitado ?? 0) }}</td>
            </tr>
        </table>
    @endforeach

    <table class="form016-table">
        <tr>
            <td class="form016-blue" style="width: 35%">• Breve descripcion en que se destinara el excedente</td>
            <td>{{ $value($accion->descripcion_excedente) }}</td>
        </tr>
        <tr>
            <td class="form016-blue">• Mecanismo de administracion de la accion</td>
            <td>FUNDAUNAH {{ $checkbox(str($accion->mecanismo_administracion)->lower()->contains('fundaunah')) }} &nbsp;&nbsp; Tesoreria de la UNAH {{ $checkbox(str($accion->mecanismo_administracion)->lower()->contains('tesorer')) }}</td>
        </tr>
    </table>

    <div class="form016-section">• FIRMAS</div>
    <table class="form016-table">
        <tr>
            <td class="form016-gray form016-center">Jefe de Departamento</td>
            <td class="form016-gray form016-center">Comite de vinculacion</td>
            <td class="form016-gray form016-center">Decano(a) o Director(a) del Centro Regional</td>
        </tr>
        <tr>
            <td class="form016-signature">Nombre: {{ $firmasPorRol->get('Jefe de Departamento')?->nombre_firmante }}<br><br>Firma:</td>
            <td class="form016-signature">Nombre: {{ $firmasPorRol->get('Comité de vinculación')?->nombre_firmante ?? $firmasPorRol->get('Comite de vinculacion')?->nombre_firmante }}<br><br>Firma:</td>
            <td class="form016-signature">Nombre: {{ $firmasPorRol->get('Decano(a) o Director(a) del Centro Regional')?->nombre_firmante }}<br><br>Nombre, firma y sello:</td>
        </tr>
    </table>
</div>
