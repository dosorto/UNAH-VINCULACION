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
    'form_dvus_018_template' => storage_path('app/templates/form-dvus-018.docx'),
    'form_dvus_018_expected_pages' => 11,
    'solicitud_practica_pps_template' => storage_path('app/templates/solicitud-practica-pps.docx'),
    // Ubicación de cada campus en el pie de la solicitud de práctica (si falta, se usa su dirección).
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
];
