@include('components.fichas.partials.institutional-pdf-chrome-styles')

<style>
    @page { size: letter portrait; margin: 0; }
    * { box-sizing: border-box; }
    body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 9pt; line-height: 1.2; margin: 0; }

    .registro-content { margin: 185pt 38pt 96pt; position: relative; z-index: 2; }
    .registro-meta { font-size: 9pt; font-weight: bold; line-height: 1.3; margin-bottom: 6pt; }
    .registro-meta strong { color: #002060; }

    h1.registro-title { color: #002060; font-family: Arial, Helvetica, sans-serif; font-size: 13pt; line-height: 1.1; margin: 8pt 0 14pt; text-align: center; text-transform: uppercase; }

    .registro-body { font-size: 9.5pt; line-height: 1.35; margin: 0 0 8pt; text-align: justify; }
    .registro-body strong { color: #002060; }

    .registro-fecha { font-size: 9.5pt; line-height: 1.3; margin: 6pt 0 10pt; text-align: justify; }

    .registro-signature { height: 130pt; margin-top: 15pt; page-break-inside: avoid; position: relative; text-align: center; }
    .registro-signature-assets { height: 100pt; margin: 0 auto; position: relative; width: 260pt; }
    .registro-signature-firma { left: 50pt; max-height: 85pt; max-width: 220pt; position: absolute; top: 8pt; transform: translateX(-50%); }
    .registro-signature-sello { left: 55%; max-height: 75pt; max-width: 95pt; position: absolute; top: 20pt; transform: translateX(-50%); z-index: 2; }
    .registro-signature-placeholder { height: 100pt; }
    .registro-signature-name { border-top: .8pt solid #111; display: inline-block; font-size: 9pt; font-weight: bold; min-width: 260pt; padding-top: 3pt; line-height: 1.3; }

    .registro-observacion { border-left: 2pt solid #ffc400; color: #000; font-size: 7.5pt; font-weight: bold; margin-top: 12pt; padding-left: 6pt; text-align: justify; line-height: 1.2; }

    .registro-bottom-row { display: table; width: 100%; margin-top: 14pt; }
    .registro-vigencia { display: table-cell; text-align: left; vertical-align: bottom; width: 50%; }
    .registro-vigencia-box { border: .8pt solid #002060; color: #002060; display: inline-block; font-size: 8pt; font-weight: bold; padding: 3pt 6pt; }
    .registro-validation { display: table-cell; text-align: right; vertical-align: bottom; width: 50%; }
    .registro-validation p { font-size: 7.5pt; font-style: italic; margin: 0; text-align: right; }

    .registro-academic-year { font-size: 8pt; font-weight: bold; color: #002060; margin-top: 10pt; text-align: center; }
</style>
