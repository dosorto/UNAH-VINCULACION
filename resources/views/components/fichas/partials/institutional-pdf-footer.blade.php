<footer class="institutional-pdf-footer">
    <table><tr>
        <td class="institutional-pdf-footer-distintivos"><img src="file://{{ public_path('images/Image/image.png') }}" alt="Distintivos institucionales"></td>
        <td class="institutional-pdf-footer-lema">{{ $institutionalAcademicYear ?? 'Año Académico "Doctora María Elena Botazzi"' }}<br>{{ $institutionalMotto ?? '"La Educación es la primera necesidad de la República"' }}</td>
        <td class="institutional-pdf-footer-block"><img src="file://{{ public_path('images/imagenes/barran.png') }}" alt=""></td>
    </tr></table>
    <table><tr><td class="institutional-pdf-footer-page">Página <span class="institutional-pdf-footer-page-number"></span>{{ filled($institutionalPageTotal ?? null) ? ' de '.$institutionalPageTotal : '' }}</td></tr></table>
    <img class="institutional-pdf-footer-line" src="file://{{ public_path('images/enf/form-018-footer.png') }}" alt="Universidad Nacional Autónoma de Honduras">
</footer>
