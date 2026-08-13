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
];
