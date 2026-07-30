@php
    $pdfMode = $isPdf ?? true;
    $variant = $institutionalVariant ?? 'form';
    $asset = static fn (string $path) => $pdfMode ? 'file://'.public_path($path) : asset($path);
@endphp

@if($pdfMode)
    <img class="institutional-pdf-accent institutional-pdf-accent--{{ $variant }}" src="{{ $asset('assets/pdf/common/rectangulo_amarillo.png') }}" alt="">
@endif
<header class="{{ $pdfMode ? 'institutional-pdf-header institutional-pdf-header--'.$variant : 'form-header' }}">
    <table class="institutional-pdf-header-table">
        <tr>
            <td class="institutional-pdf-brand"><img src="{{ $asset('assets/pdf/common/vra.png') }}" alt="UNAH, Vicerrectoría Académica y Dirección de Vinculación Universidad-Sociedad"></td>
            <td class="institutional-pdf-contact">
                {{ $institutionalEmail ?? 'vinculacion.sociedad@unah.edu.hn' }}<br>
                Tel. {{ $institutionalPhone ?? '2216-6100 Ext. 110576' }}
            </td>
        </tr>
        @if($variant === 'form')
            <tr>
                <td class="institutional-pdf-title">{{ $institutionalTitle ?? 'FORMULARIO DE REGISTRO DE PROYECTO DE VINCULACIÓN' }}<br>{{ $institutionalSubtitle ?? 'DE DESARROLLO LOCAL Y REGIONAL' }}</td>
                <td class="institutional-pdf-code">{{ $institutionalCode ?? 'FORM-DVUS-001' }}</td>
            </tr>
        @else
            <tr>
                <td class="institutional-pdf-number">{!! $institutionalNumber ?? '' !!}</td>
                <td class="institutional-pdf-validation-cell">
                    <span class="institutional-pdf-validation">Código:<br>{{ $institutionalCode ?? '' }}</span>
                    @if(filled($institutionalQr ?? null))
                        <img class="institutional-pdf-qr" src="{{ $institutionalQr }}" alt="Código QR de verificación">
                    @endif
                </td>
            </tr>
        @endif
    </table>
</header>
