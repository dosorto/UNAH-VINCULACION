<style>
    @page {
        size: letter portrait;
        margin: 37mm 8mm 13mm 10mm;
    }

    body {
        background: #fff !important;
        color: #111 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 8px !important;
        line-height: 1.18 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .container {
        border: 0 !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        max-width: none !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .pdf-content-wrapper {
        background: transparent !important;
        display: block !important;
        margin: 0 !important;
    }

    .pdf-running-header {
        height: 30mm;
        left: 0;
        position: fixed;
        right: 0;
        top: -32mm;
        z-index: 20;
    }

    .form-header-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 100%;
    }

    .form-header-table td {
        border: 0 !important;
        padding: 0 !important;
    }

    .form-header-brand {
        height: 17mm;
        text-align: center;
        vertical-align: middle;
    }

    .form-header-brand img {
        height: auto;
        max-height: 17mm;
        width: 76%;
    }

    .form-header-title {
        color: #001b5d;
        font-size: 8.6px;
        font-weight: 700;
        line-height: 1.12;
        padding: 1.6mm 3mm 0 0 !important;
        text-align: center;
        vertical-align: top;
        width: 74%;
    }

    .form-header-contact {
        border-left: 1px solid #001b5d !important;
        color: #001b5d;
        font-size: 7px;
        font-weight: 700;
        line-height: 1.22;
        padding: 1.8mm 0 0 3mm !important;
        text-align: left;
        vertical-align: top;
        width: 26%;
    }

    .pdf-yellow-marker {
        height: 22mm;
        left: -10mm;
        position: fixed;
        top: -32mm;
        width: 4.8mm;
        z-index: 30;
    }

    .pdf-watermark {
        height: auto;
        opacity: .10;
        position: fixed;
        right: -46mm;
        top: 72mm;
        width: 106mm;
        z-index: -1000;
    }

    .section1,
    .section2,
    .section3,
    .section4 {
        margin: 0 0 2.5mm !important;
    }

    .section-title {
        background: #ebeeef !important;
        border-left: 3px solid #ffc400;
        color: #001b5d !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 8.5px !important;
        font-style: normal !important;
        font-weight: 700 !important;
        line-height: 1.15 !important;
        margin: 2.5mm 0 1mm !important;
        padding: 1.2mm 1.8mm !important;
        page-break-after: avoid !important;
    }

    .table_datos1,
    .table_datos2,
    .table_datos3,
    .table_datos4,
    .table_datos5,
    .table_datos6,
    .table_datos7,
    .pdf-table {
        border-collapse: collapse !important;
        margin: 0 0 2.5mm !important;
        table-layout: fixed !important;
        width: 100% !important;
    }

    .table_datos1 td,
    .table_datos1 th,
    .table_datos2 td,
    .table_datos2 th,
    .table_datos3 td,
    .table_datos3 th,
    .table_datos4 td,
    .table_datos4 th,
    .table_datos5 td,
    .table_datos5 th,
    .table_datos6 td,
    .table_datos6 th,
    .table_datos7 td,
    .table_datos7 th,
    .pdf-table td,
    .pdf-table th {
        border: .6px solid #222 !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 7.5px !important;
        height: auto !important;
        line-height: 1.16 !important;
        max-height: none !important;
        overflow: visible !important;
        overflow-wrap: break-word !important;
        padding: 1.2mm 1.4mm !important;
        vertical-align: top !important;
        white-space: normal !important;
        word-break: normal !important;
    }

    .table_datos1 .full-width1,
    .table_datos1 .header,
    .table_datos2 .header,
    .table_datos3 .header,
    .table_datos4 .header,
    .table_datos5 .header,
    .table_datos6 .header,
    .table_datos7 .header {
        background: #001b5d !important;
        color: #fff !important;
        font-style: normal !important;
        font-weight: 700 !important;
        text-align: center !important;
        vertical-align: middle !important;
    }

    .sub-header,
    .sub-header1,
    .sub-header2,
    .sub-header3,
    .sub-header4,
    .sub-headeri,
    .sub-headert {
        background: #ebeeef !important;
        color: #111 !important;
        font-style: normal !important;
        font-weight: 600 !important;
        vertical-align: middle !important;
    }

    .pdf-text-block,
    input.input-field,
    textarea.input-field,
    textarea.input-field-multiline {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        box-sizing: border-box !important;
        display: block !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 7.5px !important;
        height: auto !important;
        line-height: 1.2 !important;
        margin: 0 !important;
        max-height: none !important;
        min-width: 0 !important;
        overflow: visible !important;
        overflow-wrap: break-word !important;
        padding: 0 !important;
        white-space: normal !important;
        width: 100% !important;
        word-break: normal !important;
    }

    .date-container {
        display: table !important;
        table-layout: fixed !important;
        width: 100% !important;
    }

    .date-part {
        display: table-cell !important;
        vertical-align: middle !important;
    }

    input[type="checkbox"] {
        height: 9px !important;
        margin: 1px auto 0 !important;
        vertical-align: middle !important;
        width: 9px !important;
    }

    ul {
        margin: 0 !important;
        padding-left: 3.5mm !important;
    }

    p {
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 7.5px !important;
        font-style: normal !important;
        line-height: 1.2 !important;
        margin: 0 0 1mm !important;
    }

    tr {
        page-break-inside: avoid !important;
    }

    thead {
        display: table-header-group;
    }

    .pdf-keep-together,
    .section-signatures table,
    .section-documents {
        page-break-inside: avoid !important;
    }

    .section-signatures,
    .section-documents,
    .section-annexes {
        page-break-before: auto;
    }

    .documents-note {
        border: .6px solid #222;
        border-top: 0;
        font-size: 7.2px;
        line-height: 1.25;
        padding: 1.5mm 2mm;
    }

    .documents-note p {
        margin: 0 !important;
    }

    .signature-image-cell {
        height: 30mm !important;
        text-align: center;
        vertical-align: bottom !important;
    }

    .signature-image-cell img {
        height: auto;
        max-height: 22mm;
        max-width: 42mm;
        width: auto;
    }

    .signature-digital-caption {
        color: #333;
        font-family: "Courier New", Courier, monospace !important;
        font-size: 6.5px !important;
        letter-spacing: .2px;
        line-height: 1.2 !important;
        margin-top: 1mm !important;
        text-align: center;
    }

    iframe,
    embed,
    .no-print,
    .fi-btn,
    .fi-modal {
        display: none !important;
    }
</style>
