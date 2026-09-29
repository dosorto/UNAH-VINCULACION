<?php

namespace App\Services\Documents;

use App\Models\PpsServicioSocial;
use App\Models\User;
use App\Support\Fichas\FirmaImagen;
use App\Support\PpsServicioSocial\FormDvus014Data;

/**
 * Celdas de la plantilla FORM-DVUS-014 (storage/app/templates/form-dvus-014.docx) con los datos
 * del registro, en el formato [tabla, fila, celda, valor, sinAjuste] de FormDvus018DataMapper.
 * Las tablas se numeran en el orden del formulario: 1 Información general … 9 Documentos adjuntos.
 */
class FormDvus014DataMapper
{
    public const TABLA_FIRMAS = 8;

    public const FILA_FIRMAS = 3;

    /** @return list<array{0: int, 1: int, 2: int, 3: string, 4: bool}> */
    public function cells(PpsServicioSocial $registro): array
    {
        $data = FormDvus014Data::from($registro);
        $f = $data['fields'];
        $marca = $data['checked'];
        $celdas = [];
        $poner = function (int $tabla, int $fila, int $celda, mixed $valor, bool $sinAjuste = false) use (&$celdas): void {
            $celdas[] = [$tabla, $fila, $celda, trim((string) ($valor ?? '')), $sinAjuste];
        };
        $x = static fn (bool $marcado): string => $marcado ? 'X' : '';
        $fecha = static fn (mixed $valor, string $formato): string => $valor instanceof \DateTimeInterface ? $valor->format($formato) : '';

        // I. Información general.
        foreach (['Y', 'm', 'd'] as $i => $formato) {
            $poner(1, 2, $i + 2, $fecha($f['fecha_registro'], $formato), true);
        }
        $poner(1, 3, 2, $f['facultad_centro']);
        $poner(1, 4, 2, $f['carrera']);

        // II. Datos del estudiante.
        foreach (['numero_cuenta', 'nombre_estudiante', 'celular_estudiante', 'correo_institucional', 'correo_personal'] as $i => $campo) {
            $poner(2, $i + 1, 2, $f[$campo]);
        }

        // III. Información de la práctica.
        $poner(3, 1, 3, $x($marca['tipo_pps']['pps']));
        $poner(3, 1, 5, $x($marca['tipo_pps']['servicio_social']));
        foreach (['fecha_inicio' => 2, 'fecha_finalizacion' => 5] as $campo => $columna) {
            foreach (['d', 'm', 'Y'] as $i => $formato) {
                $poner(3, 4, $columna + $i, $fecha($f[$campo], $formato), true);
            }
        }
        foreach (['carta_formal', 'carta_intenciones', 'convenio_marco'] as $i => $clave) {
            $poner(3, 5 + $i, 3, $x($marca['instrumento'][$clave]));
        }
        $poner(3, 8, 3, $x($marca['territorio']['nacional']));
        $poner(3, 9, 3, $x($marca['territorio']['internacional']));

        // IV. Datos territoriales: la práctica presencial (14) y la de teletrabajo (15) según la modalidad.
        $modalidad = $marca['modalidad'];
        $sinModalidad = ! in_array(true, $modalidad, true);
        $poner(4, 2, 2, $x($modalidad['presencial']));
        $poner(4, 2, 3, $x($modalidad['hibrida']));
        $poner(4, 2, 4, $x($modalidad['teletrabajo']));

        if ($modalidad['presencial'] || $modalidad['hibrida'] || $sinModalidad) {
            $region = $marca['region'];
            $nacional = $region['nacional'] || (! $region['extranjero'] && $marca['territorio']['nacional']);
            $extranjero = $region['extranjero'] || (! $region['nacional'] && $marca['territorio']['internacional']);
            $poner(4, 4, 3, $x($nacional));
            $poner(4, 4, 5, $x($extranjero));
            $poner(4, 5, 2, $f['pais']);
            $poner(4, 6, 2, $nacional ? ($f['departamento'] ?: $f['departamento_provincia']) : ($f['departamento_provincia'] ?: $f['departamento']));
            $poner(4, 7, 2, $f['municipio']);
            $poner(4, 8, 2, $f['aldea_ciudad']);
            $poner(4, 9, 2, $f['caserio']);
            $poner(4, 16, 2, $f['horas_presenciales']);
        }

        if ($modalidad['teletrabajo'] || $modalidad['hibrida'] || $sinModalidad) {
            $poner(4, 11, 2, $f['pais_sede_principal']);
            $poner(4, 12, 2, $f['departamento_provincia_sede_principal']);
            $poner(4, 13, 2, $f['municipio_sede_principal']);
            $poner(4, 14, 2, $f['aldea_ciudad_sede_principal']);
            $poner(4, 16, 4, $f['horas_teletrabajo']);
        }

        // V. Alcances.
        $poner(5, 2, 2, $f['descripcion_tipo_pps']);
        $poner(5, 2, 3, $f['descripcion_horas_tipo_pps_ss']);
        $poner(5, 4, 2, $f['area_realizacion']);
        $poner(5, 5, 2, $f['resumen_responsabilidades']);

        // VI. Institución / empresa.
        $poner(6, 1, 3, $x($marca['institucion_nacionalidad']['nacional']));
        $poner(6, 1, 5, $marca['institucion_nacionalidad']['pais'] ? ($f['institucion_pais'] ?: 'X') : '');
        foreach (['nombre_institucion', 'compromisos_institucion', 'direccion_institucion', 'representante_legal', 'telefono_representante', 'correo_rrhh'] as $i => $campo) {
            $poner(6, $i + 2, 2, $f[$campo]);
        }
        foreach (array_values($marca['tipo_institucion']) as $i => $marcado) {
            $poner(6, 9, $i + 2, $x($marcado));
        }
        // Sector: dos opciones por fila, con su casilla a la derecha de cada una.
        foreach (array_values($marca['sector_institucion']) as $i => $marcado) {
            $poner(6, 10 + intdiv($i, 2), $i % 2 === 0 ? 3 : 5, $x($marcado));
        }
        $poner(6, 15, 2, $f['nombre_jefe_directo']);
        foreach (['celular_jefe_directo', 'correo_jefe_directo', 'cargo_jefe_directo', 'grado_academico_jefe_directo'] as $i => $campo) {
            $poner(6, 17 + $i, 2, $f[$campo]);
        }

        // VII. Docente supervisor.
        foreach ([
            'nombre_docente_supervisor', 'numero_empleado_docente', 'celular_docente', 'correo_docente',
            'categoria_docente', 'departamento_docente', 'jornada_laboral_docente', 'ubicacion_cubiculo_docente',
        ] as $i => $campo) {
            $poner(7, $i + 1, 2, $f[$campo]);
        }

        // VIII. Firmas: coordinador(a) que llena el formulario, supervisor(a) y estudiante.
        foreach ([$this->coordinador($registro)?->nombre_completo, $f['nombre_docente_supervisor'], $f['nombre_estudiante']] as $i => $nombre) {
            $poner(self::TABLA_FIRMAS, 2, $i + 1, 'Nombre: '.$nombre);
        }

        // IX. Documentos adjuntos.
        foreach (['carta_formalizacion', 'convenio_marco'] as $i => $anexo) {
            $adjunto = $f['adjunta_'.$anexo] || filled($f['archivo_'.$anexo]);
            $poner(9, $i + 2, 3, $x($adjunto));
            $poner(9, $i + 2, 4, $x(! $adjunto));
        }

        return $celdas;
    }

    /**
     * Firmas que van como imagen en la fila de firmas, por número de celda. Solo la del
     * coordinador(a) que llena el formulario: el supervisor y el estudiante firman a mano.
     *
     * @return array<int, string> celda => ruta de la imagen
     */
    public function signatures(PpsServicioSocial $registro): array
    {
        $imagen = FirmaImagen::resolver(trim((string) $this->coordinador($registro)?->firma?->ruta_storage), true);
        $ruta = $imagen['path'] ?? null;

        return filled($ruta) && is_file($ruta) ? [1 => $ruta] : [];
    }

    /** El coordinador(a) de la carrera es quien llena el formulario. */
    private function coordinador(PpsServicioSocial $registro): ?object
    {
        return $registro->created_by
            ? User::with('empleado.firma')->find($registro->created_by)?->empleado
            : null;
    }
}
