<?php

namespace App\Services\ENF\Constancias;

use App\Models\ENF\EnfConstanciaFinalizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EnfConstanciaFinalizacionPdfGenerator
{
    public function content(EnfConstanciaFinalizacion $constancia): string
    {
        $token = Crypt::decryptString((string) $constancia->token_cifrado);
        $url = route('enf.constancias.finalizacion.verificar', ['token' => $token]);
        $qrPath = $this->temporaryQr($url);

        try {
            return Pdf::loadView('pdf.enf.constancias.finalizacion', [
                'snapshot' => $constancia->snapshot,
                'qr' => 'file://'.$qrPath,
                'firma' => $this->localPath(data_get($constancia->snapshot, 'autoridad.firma_ruta')),
                'sello' => $this->localPath(data_get($constancia->snapshot, 'autoridad.sello_ruta')),
                'verificationUrl' => $url,
            ])->setPaper('letter')->setOption('isRemoteEnabled', false)->setOption('defaultFont', 'DejaVu Sans')->output();
        } finally {
            @unlink($qrPath);
        }
    }

    private function temporaryQr(string $url): string
    {
        $directory = storage_path('app/constancias/tmp');
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('No se pudo preparar el codigo QR de la constancia.');
        }

        $temporaryPath = tempnam($directory, 'enf-constancia-qr-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('No se pudo preparar el codigo QR de la constancia.');
        }

        $path = $temporaryPath.'.svg';
        if (! @rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            throw new \RuntimeException('No se pudo preparar el codigo QR de la constancia.');
        }

        QrCode::format('svg')->size(120)->errorCorrection('M')->generate($url, $path);

        return $path;
    }

    private function localPath(?string $ruta): ?string
    {
        if (blank($ruta) || filter_var($ruta, FILTER_VALIDATE_URL)) {
            return null;
        }

        $ruta = ltrim((string) $ruta, '/');
        $ruta = str_starts_with($ruta, 'storage/') ? substr($ruta, 8) : $ruta;
        $disco = storage_path('app/public/'.$ruta);

        return is_file($disco) ? 'file://'.$disco : null;
    }
}
