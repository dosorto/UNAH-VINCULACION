{{-- Encabezado institucional (logos + contacto + barra de código). Recibe del
     padre: $hayLogo, $logoSrc. --}}
<table class="hdr">
    <tr>
        <td class="hdr-logo">
            @if($hayLogo)
                <img src="{{ $logoSrc }}" alt="UNAH · VRA · Dirección de Vinculación Universidad Sociedad">
            @else
                <strong>UNIVERSIDAD NACIONAL AUTÓNOMA DE HONDURAS</strong>
            @endif
        </td>
        <td class="hdr-contact">
            vinculacion.sociedad@unah.edu.hn<br>
            Tel. 2216-7070 Ext. 110576
        </td>
        <td class="hdr-accent"></td>
    </tr>
</table>
<div class="code-bar">FORM-DVUS-014</div>
