<?php

namespace App\Support\PpsServicioSocial;

use App\Models\PpsServicioSocial;
use Illuminate\Support\Str;

class FormDvus014Data
{
    public static function from(PpsServicioSocial $registro): array
    {
        $tipoPps = self::canonical($registro->tipo_pps_ss, [
            'Practica Profesional Supervisada' => [
                'pps',
                'practica profesional supervisada',
                'práctica profesional supervisada',
            ],
            'Servicio Social' => [
                'servicio social',
                'ss',
            ],
        ]);

        $tipoInstrumento = self::canonical($registro->tipo_instrumento, [
            'Carta formal de solicitud a la unidad académica' => [
                'carta formal',
                'carta formal solicitud',
                'carta formal de solicitud a la unidad academica',
                'carta_formal_solicitud',
            ],
            'Carta de intenciones con la UNAH' => [
                'carta de intenciones',
                'carta intenciones',
                'carta_intenciones',
            ],
            'Convenio marco con la UNAH' => [
                'convenio marco',
                'convenio_marco',
            ],
        ]);

        $modalidad = self::canonical($registro->modalidad_ejecucion, [
            '100% presencial' => [
                'presencial',
                '100 presencial',
                '100% presencial',
            ],
            'Híbrida' => [
                'hibrida',
                'híbrida',
                'mixta',
            ],
            'Teletrabajo' => [
                '100 virtual',
                '100% virtual',
                'teletrabajo',
                'virtual',
            ],
        ]);

        $territorio = self::canonical($registro->territorio_ejecucion, [
            'Nacional' => ['nacional'],
            'Internacional' => ['internacional'],
        ]);

        $region = self::canonical($registro->region, [
            'Nacional' => ['nacional', 'honduras'],
            'Extranjero' => ['extranjero', 'internacional', 'exterior'],
        ]);

        $nacionalidadInstitucion = self::canonical($registro->institucion_nacionalidad, [
            'Nacional' => ['nacional', 'honduras', 'hondurena', 'hondureña'],
            'País' => ['pais', 'país', 'extranjera', 'internacional', 'extranjero'],
        ]);

        $fields = [
            'id' => $registro->id,
            'codigo_registro' => $registro->codigo_registro,
            'fecha_registro' => $registro->created_at ?: $registro->fecha_envio,
            'fecha_revision' => $registro->fecha_revision,
            'facultad_centro' => self::clean($registro->facultad_centro),
            'carrera' => self::clean($registro->carrera),
            'numero_cuenta' => self::clean($registro->numero_cuenta),
            'nombre_estudiante' => self::clean($registro->nombre_estudiante),
            'celular_estudiante' => self::clean($registro->celular_estudiante),
            'correo_institucional' => self::clean($registro->correo_institucional),
            'correo_personal' => self::clean($registro->correo_personal),
            'tipo_pps_ss' => $tipoPps ?: self::clean($registro->tipo_pps_ss),
            'fecha_inicio' => $registro->fecha_inicio,
            'fecha_finalizacion' => $registro->fecha_finalizacion,
            'tipo_instrumento' => $tipoInstrumento ?: self::clean($registro->tipo_instrumento),
            'territorio_ejecucion' => $territorio ?: self::clean($registro->territorio_ejecucion),
            'modalidad_ejecucion' => $modalidad ?: self::clean($registro->modalidad_ejecucion),
            'region' => $region ?: self::clean($registro->region),
            'pais' => self::clean($registro->pais ?: ($territorio === 'Nacional' ? 'Honduras' : null)),
            'departamento_provincia' => self::clean($registro->departamento_provincia),
            'departamento' => self::clean($registro->departamento),
            'municipio' => self::clean($registro->municipio),
            'aldea_ciudad' => self::clean($registro->aldea_ciudad),
            'caserio' => self::clean($registro->caserio),
            'pais_sede_principal' => self::clean($registro->pais_sede_principal),
            'departamento_provincia_sede_principal' => self::clean($registro->departamento_provincia_sede_principal),
            'municipio_sede_principal' => self::clean($registro->municipio_sede_principal),
            'aldea_ciudad_sede_principal' => self::clean($registro->aldea_ciudad_sede_principal),
            'descripcion_tipo_pps' => self::clean($registro->descripcion_tipo_pps),
            'descripcion_horas_tipo_pps_ss' => self::clean($registro->descripcion_horas_tipo_pps_ss),
            'total_horas' => $registro->total_horas,
            'horas_presenciales' => $registro->horas_presenciales,
            'horas_teletrabajo' => $registro->horas_teletrabajo,
            'area_realizacion' => self::clean($registro->area_realizacion),
            'resumen_responsabilidades' => self::clean($registro->resumen_responsabilidades),
            'institucion_nacionalidad' => $nacionalidadInstitucion ?: self::clean($registro->institucion_nacionalidad),
            'institucion_pais' => self::clean($registro->institucion_pais),
            'nombre_institucion' => self::clean($registro->nombre_institucion),
            'compromisos_institucion' => self::clean($registro->compromisos_institucion),
            'direccion_institucion' => self::clean($registro->direccion_institucion),
            'representante_legal' => self::clean($registro->representante_legal),
            'telefono_representante' => self::clean($registro->telefono_representante),
            'correo_rrhh' => self::clean($registro->correo_rrhh),
            'tipo_institucion' => self::clean($registro->tipo_institucion),
            'sector_institucion' => self::clean($registro->sector_institucion),
            'nombre_jefe_directo' => self::clean($registro->nombre_jefe_directo),
            'celular_jefe_directo' => self::clean($registro->celular_jefe_directo),
            'correo_jefe_directo' => self::clean($registro->correo_jefe_directo),
            'cargo_jefe_directo' => self::clean($registro->cargo_jefe_directo),
            'grado_academico_jefe_directo' => self::clean($registro->grado_academico_jefe_directo),
            'nombre_docente_supervisor' => self::clean($registro->nombre_docente_supervisor),
            'numero_empleado_docente' => self::clean($registro->numero_empleado_docente),
            'celular_docente' => self::clean($registro->celular_docente),
            'correo_docente' => self::clean($registro->correo_docente),
            'categoria_docente' => self::clean($registro->categoria_docente),
            'departamento_docente' => self::clean($registro->departamento_docente),
            'jornada_laboral_docente' => self::clean($registro->jornada_laboral_docente),
            'ubicacion_cubiculo_docente' => self::clean($registro->ubicacion_cubiculo_docente),
            'adjunta_carta_formalizacion' => (bool) $registro->adjunta_carta_formalizacion,
            'archivo_carta_formalizacion' => self::clean($registro->archivo_carta_formalizacion),
            'adjunta_convenio_marco' => (bool) $registro->adjunta_convenio_marco,
            'archivo_convenio_marco' => self::clean($registro->archivo_convenio_marco),
        ];

        return [
            'fields' => $fields,
            'checked' => [
                'tipo_pps' => [
                    'pps' => $tipoPps === 'Practica Profesional Supervisada',
                    'servicio_social' => $tipoPps === 'Servicio Social',
                ],
                'instrumento' => [
                    'carta_formal' => $tipoInstrumento === 'Carta formal de solicitud a la unidad académica',
                    'carta_intenciones' => $tipoInstrumento === 'Carta de intenciones con la UNAH',
                    'convenio_marco' => $tipoInstrumento === 'Convenio marco con la UNAH',
                ],
                'territorio' => [
                    'nacional' => $territorio === 'Nacional',
                    'internacional' => $territorio === 'Internacional',
                ],
                'region' => [
                    'nacional' => $region === 'Nacional',
                    'extranjero' => $region === 'Extranjero',
                ],
                'institucion_nacionalidad' => [
                    'nacional' => $nacionalidadInstitucion === 'Nacional',
                    'pais' => $nacionalidadInstitucion === 'País',
                ],
                'modalidad' => [
                    'presencial' => $modalidad === '100% presencial',
                    'hibrida' => $modalidad === 'Híbrida',
                    'teletrabajo' => $modalidad === 'Teletrabajo',
                ],
                'tipo_institucion' => self::checkedMap($registro->tipo_institucion, [
                    'gobierno_nacional' => 'Gobierno Nacional',
                    'gobierno_municipal' => 'Gobierno Municipal',
                    'ong' => 'ONG',
                    'sociedad_civil' => 'Sociedad civil organizada',
                    'sector_privado' => 'Sector Privado',
                    'internacional' => 'Internacional',
                ]),
                'sector_institucion' => self::checkedMap($registro->sector_institucion, [
                    'agricultura' => 'Agricultura, alimentacion y silvicultura',
                    'energia_mineria' => 'Energia y mineria',
                    'produccion' => 'Produccion',
                    'servicios_privados' => 'Sectores de servicios privados',
                    'infraestructura' => 'Infraestructura, construccion y sectores relacionados',
                    'educacion' => 'Educacion e investigacion',
                    'servicios_publicos' => 'Servicios y funcion publicos',
                    'transporte' => 'Transporte, transporte maritimo y aereo',
                ]),
            ],
            'missing' => self::missingFields($fields, $registro, $territorio, $modalidad),
        ];
    }

    private static function missingFields(
        array $fields,
        PpsServicioSocial $registro,
        ?string $territorio,
        ?string $modalidad
    ): array {
        $notApplicable = [];

        if ($territorio === 'Nacional') {
            $notApplicable[] = 'departamento_provincia';
        }

        if ($territorio === 'Internacional') {
            $notApplicable[] = 'departamento';
        }

        $hasTeleworkData = collect([
            $registro->pais_sede_principal,
            $registro->departamento_provincia_sede_principal,
            $registro->municipio_sede_principal,
            $registro->aldea_ciudad_sede_principal,
            $registro->horas_teletrabajo,
        ])->contains(fn ($value): bool => self::clean($value) !== null);

        if ($modalidad === '100% presencial' && !$hasTeleworkData) {
            array_push(
                $notApplicable,
                'pais_sede_principal',
                'departamento_provincia_sede_principal',
                'municipio_sede_principal',
                'aldea_ciudad_sede_principal',
                'horas_teletrabajo'
            );
        }

        if (!$registro->adjunta_carta_formalizacion) {
            $notApplicable[] = 'archivo_carta_formalizacion';
        }

        if (!$registro->adjunta_convenio_marco) {
            $notApplicable[] = 'archivo_convenio_marco';
        }

        return collect($fields)
            ->filter(fn ($value): bool => $value === null || $value === '')
            ->keys()
            ->diff($notApplicable)
            ->values()
            ->all();
    }

    private static function checkedMap(?string $value, array $options): array
    {
        $normalizedValue = self::normalize($value);

        return collect($options)
            ->mapWithKeys(fn (string $option, string $key): array => [
                $key => $normalizedValue !== '' && $normalizedValue === self::normalize($option),
            ])
            ->all();
    }

    private static function canonical(?string $value, array $options): ?string
    {
        $normalizedValue = self::normalize($value);

        foreach ($options as $label => $aliases) {
            foreach (array_merge([$label], $aliases) as $alias) {
                if ($normalizedValue !== '' && $normalizedValue === self::normalize($alias)) {
                    return $label;
                }
            }
        }

        return null;
    }

    private static function normalize(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private static function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || self::normalize($value) === 'n a') {
            return null;
        }

        return $value;
    }
}
