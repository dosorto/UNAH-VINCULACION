<?php

return [
    'enabled' => filter_var(env('LEGACY_DB_IMPORT_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'dump_path' => env(
        'LEGACY_DB_DUMP_PATH',
        storage_path('app/private/imports/db_vinculacion.sql'),
    ),
    'dump_sha256' => env('LEGACY_DB_DUMP_SHA256'),
];
