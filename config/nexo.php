<?php


return [
    'cargos_firmas' => [
        'Creador',
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
        'En curso',

        'Aprobado',
        'Rechazado',
        'Inscrito',
        'Finalizado',
        'Cancelado',

        'Borrador',
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
        'revisores_constancia' => [
            [
                'descripcion' => 'Constancia',
                'cargo' => 'Creador',
                'estado' => 'Inscrito',
                'estado_siguiente' => 'En revision final',
            ],
            [
                'descripcion' => 'Constancia',
                'cargo' => 'Director Vinculacion',
                'estado' => 'En revision final',
                'estado_siguiente' => 'Aprobado',
            ],
        ]
    ]
];
