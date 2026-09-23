<?php

return [
    'dashboard' => [
        /*
         * Roles cuyo panel se limita a su departamento académico en vez de a
         * su centro. Todos los demás con permiso director.proyectos usan el
         * centro. Va aquí y no en un match dentro del resolver para que
         * añadir un rol no exija tocar código.
         */
        'ambitos_por_rol' => [
            'Jefe Departamento' => 'departamento',
        ],

        // Segundos de caché de las métricas agregadas. 0 la desactiva.
        // Las listas accionables (pendientes, mis formularios) nunca se cachean.
        'cache_ttl' => env('PANEL_CACHE_TTL', 120),

        // Primer año con datos; acota el eje de las series temporales.
        'anio_inicio' => 2025,

        /*
         * Formularios que muestra el panel estadístico.
         *
         * NEXO digitaliza más de treinta formularios, cada uno con su flujo y
         * sus estados. El panel no conoce ninguno: solo esta lista. De cada
         * formulario pregunta en qué estado general está cada trámite, en qué
         * etapa espera y qué etapas tiene su flujo configurado
         * (App\Support\Dashboard\Formularios\FormularioPanel).
         *
         * Para dar de alta uno nuevo basta con añadir su entrada:
         *
         *  - Si usa el motor común (App\Concerns\TieneFlujoPorEtapas: estado en
         *    `estado_proyecto` y firmas en `firma_proyecto`), se declara con
         *    FormularioMotorComun indicando su modelo y columnas. No hace falta
         *    escribir una clase.
         *  - Si tiene un motor propio —como ENF— necesita una clase que
         *    implemente FormularioPanel.
         *
         * `tipoAccion` es el código de `vinculacion_tipos_accion` con el que
         * se agrupa en el panel. Ninguna vista ni servicio cambia al añadir uno.
         */
        'formularios' => [
            [
                'clase' => \App\Support\Dashboard\Formularios\FormularioProyecto::class,
                'codigo' => 'FORM-DVUS-001',
                'nombre' => 'Proyecto de desarrollo local y regional',
                'tipoAccion' => 'DESARROLLO_LOCAL_REGIONAL',
            ],
            [
                'clase' => \App\Support\Dashboard\Formularios\FormularioProyecto::class,
                'codigo' => 'FORM-DVUS-015',
                'nombre' => 'Proyecto de voluntariado',
                'tipoAccion' => 'VOLUNTARIADO',
            ],
            [
                // Proyectos heredados sin tipo de acción: sin esta entrada no
                // aparecerían en ningún formulario.
                'clase' => \App\Support\Dashboard\Formularios\FormularioProyecto::class,
                'codigo' => 'PROYECTO-SIN-TIPO',
                'nombre' => 'Proyectos sin tipo de acción',
                'tipoAccion' => null,
            ],
            [
                'clase' => \App\Support\Dashboard\Formularios\FormularioMotorComun::class,
                'codigo' => 'FORM-DVUS-014',
                'nombre' => 'PPS y servicio social',
                'tipoAccion' => 'PPS_VOLUNTARIADO_GESTION_RIESGO',
                'modelo' => \App\Models\PpsServicioSocial::class,
                // La facultad se guarda como texto: no se puede acotar por centro.
                'columnaAutor' => 'created_by',
            ],
            [
                'clase' => \App\Support\Dashboard\Formularios\FormularioMotorComun::class,
                'codigo' => 'FORM-DVUS-013',
                'nombre' => 'Pasantía universitaria',
                'tipoAccion' => 'PASANTIAS',
                'modelo' => \App\Models\Pasantia::class,
                'columnaAutor' => 'created_by',
            ],
            [
                'clase' => \App\Support\Dashboard\Formularios\FormularioEnf::class,
                'codigo' => 'FORM-DVUS-016',
                'nombre' => 'Educación no formal: certificado',
            ],
            [
                'clase' => \App\Support\Dashboard\Formularios\FormularioEnf::class,
                'codigo' => 'FORM-DVUS-018',
                'nombre' => 'Educación no formal: acción',
            ],
        ],
    ],

    'cargos_firmas' => [
        // 'Creador',
        'Coordinador Proyecto',
        'Enlace Vinculacion',
        'Jefe Departamento',
        'Director centro',
        'Revisor Vinculacion',
        'Director Vinculacion',
    ],

    'estados_proyecto' => [
        'Coordinador Proyecto',
        'Enlace Vinculacion',
        'Jefe Departamento',
        'Director centro',
        'En revision',
        'En revision final',

        'Subsanacion',
        'En curso', // Compatibilidad con expedientes históricos.
        'Registrado',

        'Aprobado',
        'Rechazado',
        'Inscrito',
        'Finalizado',
        'Cancelado',

        'Borrador',
        'Autoguardado',
        'Actualizacion realizada',
        'Informe Final Habilitado',
    ],

    'firmas_cargos' => [
        'revisores_documento_proyecto' => [
            [
                'descripcion' => 'Proyecto',
                'cargo' => 'Coordinador Proyecto',
                'estado' => 'Coordinador Proyecto',
                'estado_siguiente' => 'Enlace Vinculacion',
            ],
            [
                'descripcion' => 'Proyecto',
                'cargo' => 'Enlace Vinculacion',
                'estado' => 'Enlace Vinculacion',
                'estado_siguiente' => 'Jefe Departamento',
            ],
            [
                'descripcion' => 'Proyecto',
                'cargo' => 'Jefe Departamento',
                'estado' => 'Jefe Departamento',
                'estado_siguiente' => 'Director centro',
            ],
            [
                'descripcion' => 'Proyecto',
                'cargo' => 'Director centro',
                'estado' => 'Director centro',
                'estado_siguiente' => 'En revision',
            ],
            [
                'descripcion' => 'Proyecto',
                'cargo' => 'Revisor Vinculacion',
                'estado' => 'En revision',
                'estado_siguiente' => 'En revision final',
            ],
            [
                'descripcion' => 'Proyecto',
                'cargo' => 'Director Vinculacion',
                'estado' => 'En revision final',
                'estado_siguiente' => 'En curso',
            ],

        ],

        'revisores_documento_intermedio' => [
            [
                'descripcion' => 'Documento_intermedio',
                'cargo' => 'Enlace Vinculacion',
                'estado' => 'Enlace Vinculacion',
                'estado_siguiente' => 'Jefe Departamento',
            ],
            [
                'descripcion' => 'Documento_intermedio',
                'cargo' => 'Jefe Departamento',
                'estado' => 'Jefe Departamento',
                'estado_siguiente' => 'En revision',
            ],
            [
                'descripcion' => 'Documento_intermedio',
                'cargo' => 'Revisor Vinculacion',
                'estado' => 'En revision',
                'estado_siguiente' => 'Aprobado',
            ],

        ],
        'revisores_documento_final' => [
            [
                'descripcion' => 'Documento_final',
                'cargo' => 'Enlace Vinculacion',
                'estado' => 'Enlace Vinculacion',
                'estado_siguiente' => 'Jefe Departamento',
            ],
            [
                'descripcion' => 'Documento_final',
                'cargo' => 'Jefe Departamento',
                'estado' => 'Jefe Departamento',
                'estado_siguiente' => 'Director centro',
            ],
            [
                'descripcion' => 'Documento_final',
                'cargo' => 'Director centro',
                'estado' => 'Director centro',
                'estado_siguiente' => 'En revision',

            ],
            [
                'descripcion' => 'Documento_final',
                'cargo' => 'Revisor Vinculacion',
                'estado' => 'En revision',
                'estado_siguiente' => 'Aprobado',
            ],
        ],
        'revisores_ficha_actualizacion' => [
            [
                'descripcion' => 'Ficha_actualizacion',
                'cargo' => 'Coordinador Proyecto',
                'estado' => 'Coordinador Proyecto',
                'estado_siguiente' => 'Enlace Vinculacion',
            ],
            [
                'descripcion' => 'Ficha_actualizacion',
                'cargo' => 'Enlace Vinculacion',
                'estado' => 'Enlace Vinculacion',
                'estado_siguiente' => 'Jefe Departamento',
            ],
            [
                'descripcion' => 'Ficha_actualizacion',
                'cargo' => 'Jefe Departamento',
                'estado' => 'Jefe Departamento',
                'estado_siguiente' => 'Director centro',
            ],
            [
                'descripcion' => 'Ficha_actualizacion',
                'cargo' => 'Director centro',
                'estado' => 'Director centro',
                'estado_siguiente' => 'En revision',
            ],
            [
                'descripcion' => 'Ficha_actualizacion',
                'cargo' => 'Revisor Vinculacion',
                'estado' => 'En revision',
                'estado_siguiente' => 'Actualizacion realizada',
            ],
        ],
        //  'revisores_constancia' => [
        //      [
        //          'descripcion' => 'Constancia',
        //          'cargo' => 'Creador',
        //          'estado' => 'Inscrito',
        //          'estado_siguiente' => 'En revision final',
        //      ],
        //      [
        //          'descripcion' => 'Constancia',
        //          'cargo' => 'Director Vinculacion',
        //          'estado' => 'En revision final',
        //          'estado_siguiente' => 'Aprobado',
        //      ],
        //  ]
    ],
];
