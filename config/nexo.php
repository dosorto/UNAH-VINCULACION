<?php


return [
    'cargos_firmas' => [
        'Coordinador Proyecto',
        'Enlace Vinculacion',
        'Jefe Departamento',
        'Director centro',
    ],

    'estados_proyecto' => [
        'Coordinador Proyecto',
        'Enlace Vinculacion',
        'Jefe Departamento',
        'Director centro',
        'En revision',

        'Subsanacion',
        'En curso',

        'Aprobado',
        'Rechazado',
        'Inscrito',
        'Finalizado',
        'Cancelado',
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

            ]
        ]
    ]
];
