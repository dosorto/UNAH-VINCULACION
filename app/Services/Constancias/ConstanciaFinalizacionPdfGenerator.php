<?php

namespace App\Services\Constancias;

use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ConstanciaFinalizacionPdfGenerator
{
    public function content(ConstanciaFinalizacionProyecto $constancia): string
    {
        $token = Crypt::decryptString((string) $constancia->token_cifrado);
        $url = route('constancias.finalizacion.verificar', ['token' => $token]);
        $qrPath = $this->temporaryQr($url);

        try {
            $pdf = Pdf::loadView('pdf.constancias.constancia-finalizacion-proyecto', [
                'snapshot' => $constancia->snapshot,
                'qr' => 'file://'.$qrPath,
                'firma' => $this->localPath(data_get($constancia->snapshot, 'autoridad.firma_ruta')),
                'sello' => $this->localPath(data_get($constancia->snapshot, 'autoridad.sello_ruta')),
                'header' => 'file://'.public_path('images/enf/form-018-header.png'),
                'footer' => 'file://'.public_path('images/enf/form-018-footer.png'),
                'watermark' => 'file://'.public_path('images/enf/form-018-watermark.png'),
                'institucional' => [
                    'telefono' => '2216-7070 Ext. 110576',
                    'correo' => 'vinculacion.sociedad@unah.edu.hn',
                ],
            ])->setPaper('letter')->setOption('isRemoteEnabled', false)->setOption('defaultFont', 'DejaVu Sans');

            return $pdf->output();
        } finally {
            @unlink($qrPath);
        }
    }

    private function temporaryQr(string $url): string
    {
        $directory = storage_path('app/constancias/tmp');

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('No se pudo preparar el código QR de la constancia.');
        }

        $temporaryPath = tempnam($directory, 'constancia-qr-');

        if ($temporaryPath === false) {
            throw new \RuntimeException('No se pudo preparar el código QR de la constancia.');
        }

        $path = $temporaryPath.'.svg';

        if (! @rename($temporaryPath, $path)) {
            @unlink($temporaryPath);

            throw new \RuntimeException('No se pudo preparar el código QR de la constancia.');
        }

        try {
            QrCode::format('svg')->size(120)->errorCorrection('M')->generate($url, $path);
        } catch (\Throwable $exception) {
            @unlink($path);

            throw $exception;
        }

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
