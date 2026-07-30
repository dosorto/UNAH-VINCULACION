@include('components.fichas.partials.institutional-pdf-header', [
    'isPdf' => true,
    'institutionalVariant' => 'constancia',
    'institutionalPhone' => $institucional['telefono'],
    'institutionalEmail' => $institucional['correo'],
    'institutionalQr' => $qr,
    'institutionalNumber' => '',
    'institutionalCode' => data_get($snapshot, 'constancia.codigo_validacion'),
])
