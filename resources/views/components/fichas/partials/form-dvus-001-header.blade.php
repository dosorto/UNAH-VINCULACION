@php
    $pdfMode = ! empty($isPdf);
    $vraSrc = $pdfMode
        ? public_path('assets/pdf/common/vra.png')
        : asset('assets/pdf/common/vra.png');
    $rectanguloSrc = $pdfMode
        ? public_path('assets/pdf/common/rectangulo_amarillo.png')
        : asset('assets/pdf/common/rectangulo_amarillo.png');
    $solSrc = $pdfMode
        ? public_path('assets/pdf/common/sol_gris.png')
        : asset('assets/pdf/common/sol_gris.png');
@endphp

{{-- HEADER INSTITUCIONAL --}}
@if ($pdfMode)
    <img class="pdf-yellow-marker" src="{{ $rectanguloSrc }}" alt="">
    <img class="pdf-watermark" src="{{ $solSrc }}" alt="">
@endif

<div class="{{ $pdfMode ? 'pdf-running-header' : 'form-header' }}">
    <table class="form-header-table">
        <tr>
            <td class="form-header-brand" colspan="2">
                <img src="{{ $vraSrc }}" alt="UNAH, Vicerrectoría Académica y Dirección de Vinculación Universidad Sociedad">
            </td>
        </tr>
        <tr>
            <td class="form-header-title">
                FORM-DVUS-001<br>
                FORMULARIO DE REGISTRO DE PROYECTO DE VINCULACIÓN<br>
                DE DESARROLLO LOCAL Y REGIONAL
            </td>
            <td class="form-header-contact">
                <span>vinculacion.sociedad@unah.edu.hn</span><br>
                Tel. 2216-6100 Ext. 110576
            </td>
        </tr>
    </table>
</div>
