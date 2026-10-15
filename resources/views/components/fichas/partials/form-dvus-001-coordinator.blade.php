<table class="table_datos1 official-coordinator-table">
    <tr>
        <th class="full-width1" rowspan="3" style="width:17%;">9. Coordinador/a del Proyecto:</th>
        <td style="width:41.5%;">Nombre Completo:<br><div class="pdf-text-block">{!! $renderPdfText($coordinador?->nombre_completo) !!}</div></td>
        <td style="width:41.5%;">No. de empleado/a:<br><div class="pdf-text-block">{!! $renderPdfText($coordinador?->numero_empleado) !!}</div></td>
    </tr>
    <tr>
        <td>Correo electrónico:<br><div class="pdf-text-block">{!! $renderPdfText($coordinador?->user?->email) !!}</div></td>
        <td>Celular:<br><div class="pdf-text-block">{!! $renderPdfText($coordinador?->celular) !!}</div></td>
    </tr>
    <tr>
        <td>Categoría:<br><div class="pdf-text-block">{!! $renderPdfText($coordinador?->categoria?->nombre) !!}</div></td>
        <td>Departamento al que pertenece:<br><div class="pdf-text-block">{!! $renderPdfText($coordinador?->departamento_academico?->nombre) !!}</div></td>
    </tr>
</table>
