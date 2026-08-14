@include('components.fichas.partials.institutional-pdf-chrome-styles')

<style>
    @page { size: letter portrait; margin: 0; }
    * { box-sizing: border-box; }
    body { color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 7.1pt; line-height: 1.12; margin: 0; }

    .constancia-content { margin: 185pt 38pt 96pt; position: relative; z-index: 2; }
    .constancia-page-two { page-break-before: always; }
    .constancia-page-two:before { content: ''; display: block; height: 185pt; }
    h1 { color: #002060; font-family: Arial, Helvetica, sans-serif; font-size: 11.4pt; line-height: 1.03; margin: 10pt 0 5pt; text-align: center; }
    h2 { color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 8pt; line-height: 1; margin: 5pt 0 3pt; page-break-after: avoid; }
    p { font-size: 7.2pt; line-height: 1.1; margin: 0 0 4pt; text-align: justify; }
    .constancia-meta { font-size: 8pt; font-weight: bold; line-height: 1.25; margin-bottom: 4pt; }
    .constancia-meta strong { color: #002060; }
    .constancia-section-spacing { margin-top: 10pt; }
    .constancia-data { border-collapse: collapse; page-break-inside: avoid; table-layout: fixed; width: 100%; }
    .constancia-data th, .constancia-data td { border: .55pt solid #111; padding: 2.3pt 3.5pt; vertical-align: top; }
    .constancia-data th { background: #001b44; color: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 6.65pt; font-weight: bold; line-height: 1.03; text-align: left; }
    .constancia-data td { font-size: 6.85pt; line-height: 1.04; }
    .constancia-data .label { background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; font-weight: normal; width: 36%; }
    .constancia-data thead { display: table-header-group; }
    .constancia-data tr { page-break-inside: avoid; }

    .constancia-signature { height: 175pt; margin-top: 35pt; page-break-inside: avoid; position: relative; text-align: center; }
    .constancia-signature-assets { height: 126pt; margin: 0 auto; position: relative; width: 280pt; }
    .constancia-signature-firma { left: 50pt; max-height: 103pt; max-width: 245pt; position: absolute; top: 19pt; transform: translateX(-50%); }
    .constancia-signature-sello { left: 50%; max-height: 92pt; max-width: 108pt; position: absolute; top: 34pt; transform: translateX(-50%); z-index: 2; }
    .constancia-signature-name { border-top: .8pt solid #111; display: inline-block; font-size: 8pt; font-weight: bold; min-width: 280pt; padding-top: 2pt; }
    .constancia-note { border-left: 2pt solid #ffc400; color: #000; font-size: 7pt; font-weight: bold; margin-top: 15pt; padding-left: 5pt; text-align: justify; }
    .constancia-validation-url { font-size: 7pt; font-style: italic; margin-top: 12pt; text-align: center; }
    .constancia-footer-codigo { bottom: 95pt; color: #999; font-family: Arial, Helvetica, sans-serif; font-size: 6.3pt; left: 26pt; position: fixed; right: 26pt; z-index: 20; }
</style>
