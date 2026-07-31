@include('components.fichas.partials.institutional-pdf-header', [
    'isPdf' => $isPdf ?? false,
    'institutionalVariant' => 'form',
    'institutionalTitle' => 'FORMULARIO DE REGISTRO DE PRÁCTICA PROFESIONAL',
    'institutionalSubtitle' => 'SUPERVISADA O SERVICIO SOCIAL',
    'institutionalCode' => 'FORM-DVUS-014',
    'institutionalPhone' => '2216-7070 Ext. 110576',
])
