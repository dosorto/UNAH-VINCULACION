{{-- Solo FORM-DVUS-001. Medidas y tipografía del Word de registro. --}}
<style>
    @page { size: letter portrait; margin: 40mm 25.88mm 25.4mm 25.4mm; }
    /* Helvetica ofrece las variantes regular/negrita/cursiva incorporadas en DomPDF. */
    body, body * { font-family: Helvetica, sans-serif !important; }
    .container table td, .container table th, .container .pdf-text-block { font-family: Helvetica, sans-serif !important; }
    body { font-size: 10pt !important; line-height: 1.15 !important; }
    html, body, .container, .pdf-content-wrapper { background-color: transparent !important; }
    .official-page-header { position: fixed; top: -37mm; left: -15mm; right: -15mm; height: 27mm; z-index: 20; }
    .official-brand { width: 140mm; height: auto; }
    .official-contact { position: absolute; right: 0; top: 1mm; color: #17365d; font-size: 8pt; font-weight: bold; text-align: right; }
    .official-accent { position: fixed; top: -39mm; right: -24mm; width: 6.8mm; height: 31mm; z-index: 20; }
    .institutional-pdf-watermark { top: 76mm; right: -147mm; width: 300mm; height: auto; opacity: .45; }
    .official-page-number { position: fixed; bottom: -12mm; left: 0; right: 0; text-align: center; font-size: 10pt; }
    .official-page-number:before { content: counter(page); }
    .official-form-code { background-color: #002060; color: white; text-align: right; font-size: 14pt; font-weight: bold; line-height: 1.1; }
    .official-form-title { margin: 4mm 0 8mm; font-size: 14pt; font-weight: bold; text-align: right; line-height: 1.15; }
    .section-title { background-color: transparent !important; border: 0 !important; color: #002060 !important; font-size: 12pt !important; margin: 6mm 0 5mm !important; padding: 0 !important; }
    .table_datos1, .table_datos2, .table_datos3, .table_datos4, .table_datos5 { margin-bottom: 3mm !important; }
    .table_datos1 td, .table_datos1 th, .table_datos2 td, .table_datos2 th,
    .table_datos3 td, .table_datos3 th, .table_datos4 td, .table_datos4 th,
    .table_datos5 td, .table_datos5 th, .pdf-table td, .pdf-table th {
        font-size: 10pt !important; line-height: 1.15 !important; padding: 1.2mm !important;
        border: .5pt solid #bfbfbf !important; vertical-align: middle !important;
    }
    .pdf-text-block, input.input-field, textarea.input-field, p { font-size: 10pt !important; line-height: 1.15 !important; }
    .sub-header, .sub-header1, .sub-header2, .sub-header3, .sub-header4 { background-color: #f2f2f2 !important; }
    .table_datos1.info-general-table th.full-width1 { width: 25% !important; text-align: left !important; }
    .table_datos1.info-general-table td.sub-header { width: 25% !important; }
    .info-general-table .sub-header { font-style: italic !important; font-weight: bold !important; }
    .info-general-table .info-col-label { width: 25% !important; }
    .info-general-table .info-col-detail { width: 15% !important; }
    .pdf-choice, .beneficiary-summary th, .beneficiary-ethnicity th, .beneficiary-ethnicity td { font-size: 8pt !important; }
    .pdf-choice { background-color: #d9d9d9 !important; font-weight: bold !important; font-style: normal !important; }
    .table_datos1.info-general-table .official-choice-cell { padding: 0 !important; }
    .official-choices { width: 100%; table-layout: fixed; border-collapse: collapse; }
    .table_datos1.info-general-table .official-choices td { width: 25% !important; text-align: center !important; }
    .table_datos1.info-general-table .date-cell { padding: 0 !important; }
    .table_datos1 td.pdf-choice, .table_datos2 td.pdf-choice,
    .official-form-title, .official-form-code, .official-contact, .section-title {
        font-family: Helvetica, sans-serif !important;
    }
    .date-inner-table th, .date-inner-table td { font-size: 10pt !important; }
    .date-inner-table td { height: 7mm; }
    .date-cell { padding: 0 !important; }
    .section3 .full-width1 { font-style: normal !important; text-align: left !important; }
    .section3 td, .section3 th { font-size: 8pt !important; }
    .section3 .sub-header { background-color: #d9d9d9 !important; }
    .official-team-table td, .official-team-table th, .official-team-table .pdf-text-block { font-size: 8pt !important; }
    .official-team-table td.sub-header { background-color: #002060 !important; color: #fff !important; }
    .official-empty-row td { height: 6mm !important; }
    .official-coordinator-table td { height: 12mm !important; }
    .official-counterparty-table tr > td:first-child[colspan="1"] { width: 25% !important; }
    .table_datos2 .header { background-color: transparent !important; color: #111 !important; font-weight: normal !important; }
    .official-note, .official-note p { font-size: 8pt !important; line-height: 1.15 !important; margin: 3mm 0 !important; }
    .documents-note { border: 0; font-size: 10pt; padding: 3mm 0; }
    .documents-note p { margin-bottom: 2mm !important; }
    .documents-note ul { padding-left: 6mm !important; }
    .section-documents .section-title { color: #111 !important; font-size: 10pt !important; font-weight: normal !important; }
    .section-signatures .signature-table td.signature-title-cell { background-color: #002060 !important; color: #fff !important; font-size: 10pt !important; text-align: center; }
    .signature-table td.signature-image-cell { height: 25mm !important; }
    .signature-table th.signature-caption-cell { background-color: transparent !important; color: #111 !important; font-size: 8pt !important; font-weight: normal !important; }
    .section-signatures .signature-table { page-break-inside: avoid !important; }
</style>
