@include('components.fichas.partials.institutional-pdf-header', [
    'isPdf' => $isPdf ?? false,
    'institutionalVariant' => 'form',
    'institutionalTitle' => $institutionalTitle ?? 'FORMULARIO DE REGISTRO DE PROYECTO DE VINCULACIÓN',
    'institutionalSubtitle' => $institutionalSubtitle ?? 'DE DESARROLLO LOCAL Y REGIONAL',
    'institutionalCode' => $institutionalCode ?? 'FORM-DVUS-001',
])
