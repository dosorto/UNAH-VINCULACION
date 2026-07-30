<style>
    @page { size: letter portrait; margin: 35mm 9mm 13mm; }

    body { background: #fff !important; color: #111 !important; font-family: Arial, Helvetica, sans-serif !important; font-size: 7.15pt !important; line-height: 1.12 !important; margin: 0 !important; padding: 0 !important; }
    .container { border: 0 !important; box-sizing: border-box !important; margin: 0 !important; max-width: none !important; padding: 0 !important; width: 100% !important; }
    .pdf-content-wrapper { background: transparent !important; display: block !important; margin: 0 !important; }

    .pdf-running-header { height: 28mm; left: 0; position: fixed; right: 0; top: -31mm; z-index: 20; }
    .form-header-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .form-header-table td { border: 0 !important; padding: 0 !important; }
    .form-header-brand { vertical-align: top; width: 70%; }
    .form-header-brand img { display: block; height: auto; margin-top: .3mm; width: 270pt; }
    .form-header-contact { border-left: .6pt solid #002060 !important; color: #002060; font-size: 6.4pt; font-weight: 700; line-height: 1.2; padding: 1.3mm 2mm !important; text-align: right; vertical-align: top; width: 30%; }
    .form-header-code { background: #002060; color: #fff; font-size: 7.2pt; font-weight: 700; line-height: 4.4mm; padding: 0 2mm; text-align: right; vertical-align: middle; width: 22%; }
    .form-header-title { color: #002060; font-size: 8.1pt; font-weight: 700; letter-spacing: -.1pt; line-height: 1.06; padding-top: 1.1mm !important; text-align: center; vertical-align: middle; width: 78%; }
    .pdf-yellow-marker { height: 24mm; position: fixed; right: -5.5mm; top: -31mm; width: 4.8mm; z-index: 30; }
    .pdf-watermark { height: auto; left: 105pt; opacity: .075; position: fixed; top: 220pt; width: 292pt; z-index: 0; }

    .section1, .section2, .section3, .section4 { margin: 0 0 1.5mm !important; position: relative; z-index: 2; }
    .section-title { background: #ebeeef !important; border-left: 2.4mm solid #ffc400; color: #002060 !important; font-family: Arial, Helvetica, sans-serif !important; font-size: 7.7pt !important; font-style: normal !important; font-weight: 700 !important; line-height: 1.1 !important; margin: 1.7mm 0 .8mm !important; padding: 1mm 1.5mm !important; page-break-after: avoid !important; }

    .table_datos1, .table_datos2, .table_datos3, .table_datos4, .table_datos5, .table_datos6, .table_datos7, .pdf-table { border-collapse: collapse !important; margin: 0 0 1.5mm !important; table-layout: fixed !important; width: 100% !important; }
    .table_datos1 td, .table_datos1 th, .table_datos2 td, .table_datos2 th, .table_datos3 td, .table_datos3 th, .table_datos4 td, .table_datos4 th, .table_datos5 td, .table_datos5 th, .table_datos6 td, .table_datos6 th, .table_datos7 td, .table_datos7 th, .pdf-table td, .pdf-table th { border: .5pt solid #374151 !important; font-family: Arial, Helvetica, sans-serif !important; font-size: 6.85pt !important; line-height: 1.1 !important; overflow-wrap: break-word !important; padding: .85mm 1.1mm !important; vertical-align: top !important; word-break: normal !important; }
    .table_datos1 .full-width1, .table_datos1 .header, .table_datos2 .header, .table_datos3 .header, .table_datos4 .header, .table_datos5 .header, .table_datos6 .header, .table_datos7 .header { background: #001b44 !important; color: #fff !important; font-style: normal !important; font-weight: 700 !important; text-align: center !important; vertical-align: middle !important; }
    .sub-header, .sub-header1, .sub-header2, .sub-header3, .sub-header4, .sub-headeri, .sub-headert { background: #edf0f4 !important; color: #111 !important; font-style: normal !important; font-weight: 600 !important; vertical-align: middle !important; }
    .pdf-text-block, input.input-field, textarea.input-field, textarea.input-field-multiline { background: transparent !important; border: 0 !important; box-shadow: none !important; box-sizing: border-box !important; display: block !important; font-family: Arial, Helvetica, sans-serif !important; font-size: 6.85pt !important; line-height: 1.12 !important; margin: 0 !important; min-width: 0 !important; overflow: visible !important; overflow-wrap: break-word !important; padding: 0 !important; white-space: normal !important; width: 100% !important; word-break: normal !important; }

    .date-cell { padding: 0 !important; }
    .date-inner-table, .execution-dates-table { border-collapse: collapse; margin: 0; table-layout: fixed; width: 100%; }
    .date-inner-table th, .date-inner-table td { border: 0 !important; border-right: .5pt solid #374151 !important; padding: .7mm !important; text-align: center; }
    .date-inner-table th { background: #001b44; color: #fff; font-size: 6.2pt; }
    .date-inner-table th:last-child, .date-inner-table td:last-child, .execution-dates-table td:last-child { border-right: 0 !important; }
    .execution-dates-table td { border: 0 !important; border-right: .5pt solid #374151 !important; padding: 0 !important; vertical-align: middle !important; }
    .execution-date-label { background: #edf0f4; font-style: normal; padding: .8mm 1mm !important; width: 18%; }
    .execution-date-value { width: 32%; }

    .pdf-check { border: .7pt solid #111; display: inline-block; font-family: Arial, Helvetica, sans-serif; font-size: 7pt; font-weight: 700; height: 8pt; line-height: 7pt; margin-top: 1pt; text-align: center; vertical-align: middle; width: 8pt; }
    .pdf-check.is-checked { color: #001b44; }
    .pdf-choice { line-height: 1.08; text-align: center; }
    .pdf-choice .pdf-check { margin-top: 2pt; }
    .beneficiary-summary td { text-align: center; vertical-align: middle !important; }
    .beneficiary-summary strong { color: #001b44; display: block; font-size: 6.3pt; }
    .beneficiary-ethnicity { border-collapse: collapse; margin: 0; table-layout: fixed; width: 100%; }
    .beneficiary-ethnicity td, .beneficiary-ethnicity th { border: .45pt solid #5d6673 !important; font-size: 6.35pt !important; padding: .65mm !important; text-align: center; }
    .beneficiary-ethnicity th { background: #edf0f4; color: #111; }

    ul { margin: 0 !important; padding-left: 3.2mm !important; }
    p { font-family: Arial, Helvetica, sans-serif !important; font-size: 6.85pt !important; font-style: normal !important; line-height: 1.12 !important; margin: 0 0 .8mm !important; }
    tr { page-break-inside: avoid !important; }
    thead { display: table-header-group; }
    .pdf-keep-together, .section-signatures table, .section-documents, .section-site-execution { page-break-inside: avoid !important; }
    .documents-note { border: .5pt solid #374151; border-top: 0; font-size: 6.7pt; line-height: 1.15; padding: 1mm 1.5mm; }
    .documents-note p { margin: 0 !important; }
    .signature-image-cell { height: 28mm !important; text-align: center; vertical-align: bottom !important; }
    .signature-image-cell img { height: auto; max-height: 21mm; max-width: 42mm; width: auto; }
    .signature-digital-caption { color: #333; font-family: "Courier New", Courier, monospace !important; font-size: 6.2pt !important; line-height: 1.1 !important; margin-top: .7mm !important; text-align: center; }
    iframe, embed, .no-print, .fi-btn, .fi-modal { display: none !important; }
</style>
