<?php

return [
    'libreoffice_binary' => env('LIBREOFFICE_BINARY', '/usr/bin/libreoffice'),
    'pdfinfo_binary' => env('PDFINFO_BINARY', '/usr/bin/pdfinfo'),
    'libreoffice_candidates' => [
        '/usr/bin/libreoffice',
        '/usr/bin/soffice',
        '/opt/homebrew/bin/soffice',
        '/usr/local/bin/soffice',
        '/Applications/LibreOffice.app/Contents/MacOS/soffice',
    ],
    'pdfinfo_candidates' => [
        '/usr/bin/pdfinfo',
        '/opt/homebrew/bin/pdfinfo',
        '/usr/local/bin/pdfinfo',
    ],
    'form_dvus_013_template' => storage_path('app/templates/form-dvus-013.docx'),
    'form_dvus_014_template' => storage_path('app/templates/form-dvus-014.docx'),
    'form_dvus_018_template' => storage_path('app/templates/form-dvus-018.docx'),
    'form_dvus_018_expected_pages' => 11,
    'solicitud_practica_pps_template' => storage_path('app/templates/solicitud-practica-pps.docx'),
    'autorizacion_pps_template' => storage_path('app/templates/autorizacion-pps.docx'),
    // Ubicación de cada campus en el pie de las cartas PPS (si falta, se usa su dirección).
    'ubicacion_campus' => [
        'CU' => 'Tegucigalpa M.D.C., Honduras C.A.',
        'UNAHVS' => 'San Pedro Sula, Cortés',
        'TECDANLÍ' => 'Danlí, El Paraíso',
        'CURNO' => 'Juticalpa, Olancho',
        'CURC' => 'Comayagua, Comayagua',
        'CURLA' => 'La Ceiba, Atlántida',
        'CURLP' => 'Choluteca, Salida a San Marcos de Colón km 5',
        'CUROC' => 'Santa Rosa de Copán, Copán',
        'TECAGUAN' => 'Olanchito, Yoro',
        'ITSTELA' => 'Tela, Atlántida',
    ],
    // Nombre de cada campus con su artículo, para la autorización de PPS («en el Centro … – UNAH-CURLP»).
    'nombre_campus' => [
        'CU' => 'la Ciudad Universitaria',
        'UNAHVS' => 'la UNAH Campus Cortés',
        'TECDANLÍ' => 'el Centro Tecnológico de Danlí',
        'CURNO' => 'el Centro Universitario Regional del Nororiente',
        'CURC' => 'el Centro Universitario Regional del Centro',
        'CURLA' => 'el Centro Universitario Regional del Litoral Atlántico',
        'CURLP' => 'el Centro Universitario Regional del Litoral Pacífico',
        'CUROC' => 'el Centro Universitario Regional de Occidente',
        'TECAGUAN' => 'el Centro Tecnológico del Valle del Aguán',
        'ITSTELA' => 'el Instituto Tecnológico Superior de Tela',
    ],
];
