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
         * Familias de trámite que muestra el panel estadístico.
         *
         * NEXO digitaliza decenas de formularios y cada uno trae su propio
         * itinerario: un proyecto de vinculación recorre inscripción, informe
         * intermedio y cierre; una práctica profesional se agota en la
         * autorización del coordinador; una acción de educación no formal
         * repite el ciclo dentro de cada uno de sus procesos.
         *
         * El panel no conoce formularios, solo esta lista. Para dar de alta uno
         * nuevo basta con añadir aquí su familia:
         *
         *  - Si sigue el patrón habitual (estado actual en `estado_proyecto` y
         *    firmas por `flujos_aprobacion`), se declara con FamiliaPorEstados
         *    indicando modelo, fases y los estados de cada fase. No hace falta
         *    escribir una clase.
         *  - Si se sale del patrón —como ENF, que lleva su estado en una
         *    columna propia y sus revisiones en tablas aparte— implementa
         *    FamiliaTramite a medida.
         *
         * Ninguna vista ni servicio del panel cambia al añadir una familia.
         */
        'familias_tramite' => [
            \App\Support\Dashboard\Tramites\FamiliaProyectos::class,
            \App\Support\Dashboard\Tramites\FamiliaPps::class,
            \App\Support\Dashboard\Tramites\FamiliaEnf::class,
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
