<?php

namespace App\Services\PpsServicioSocial;

use App\Models\PpsServicioSocial;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\LibreOfficePdfConverter;
use App\Support\PpsServicioSocial\FormDvus014Data;
use App\Support\PpsServicioSocial\TratamientoDestinatario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * SOLICITUD DE PRÁCTICA del FORM-DVUS-014: llena la plantilla de Word
 * (storage/app/templates/solicitud-practica-pps.docx, marcadores {{...}}) y la convierte a PDF
 * con LibreOffice, igual que el FORM-DVUS-018.
 */
class PpsSolicitudPracticaDocumento
{
    // Caja de la firma en EMU (1 cm = 360000): hasta 6 × 1,8 cm, sin deformarla.
    private const FIRMA_ANCHO = 2160000;
    private const FIRMA_ALTO = 648000;

    private const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function __construct(private readonly LibreOfficePdfConverter $converter) {}

    /**
     * @param  array{nombre: string, cargo: string, firma?: ?string}  $firmante  firma: imagen PNG o JPEG
     * @return string contenido del PDF
     */
    public function pdf(PpsServicioSocial $pps, array $firmante): string
    {
        $plantilla = (string) config('documents.solicitud_practica_pps_template');
        if (! is_file($plantilla) || ! is_readable($plantilla)) {
            throw new RuntimeException('No se encontró la plantilla de la SOLICITUD DE PRÁCTICA. Ruta: '.$plantilla);
        }

        $directorio = storage_path('app/tmp/solicitud-practica/'.Str::random(32));
        File::ensureDirectoryExists($directorio);

        try {
            $docx = $directorio.'/solicitud-practica.docx';
            if (! copy($plantilla, $docx)) {
                throw new RuntimeException('No se pudo copiar la plantilla de la SOLICITUD DE PRÁCTICA.');
            }

            $this->llenar($docx, $pps, $firmante);

            return (string) file_get_contents($this->converter->convert($docx, $directorio, 'la SOLICITUD DE PRÁCTICA'));
        } finally {
            File::deleteDirectory($directorio);
        }
    }

    /**
     * Llena los marcadores del cuerpo, los encabezados y los pies, y pone la firma registrada
     * de quien firma; sin ella queda el espacio para firmar a mano.
     *
     * @param  array{nombre: string, cargo: string, firma?: ?string}  $firmante
     */
    public function llenar(string $docx, PpsServicioSocial $pps, array $firmante): void
    {
        $valores = $this->valores($pps, $firmante);

        foreach ($this->partesConTexto($docx) as $parte) {
            $editor = (new DocxTemplateEditor($docx, $parte))->replacePlaceholders($valores);

            if ($parte === 'word/document.xml') {
                $editor->replaceImagePlaceholder('firma', $firmante['firma'] ?? null, self::FIRMA_ANCHO, self::FIRMA_ALTO);
            }

            $editor->save();
        }
    }

    /**
     * @param  array{nombre: string, cargo: string, firma?: ?string}  $firmante
     * @return array<string, string>
     */
    public function valores(PpsServicioSocial $pps, array $firmante): array
    {
        $campos = FormDvus014Data::from($pps)['fields'];
        $valor = static fn (string $campo): string => trim((string) ($campos[$campo] ?? ''));
        $hoy = Carbon::now();
        $servicioSocial = Str::contains(Str::lower(Str::ascii($valor('tipo_pps_ss'))), 'servicio');

        return [
            'lema' => '“Año Académico '.$hoy->year.' María Elena Bottazzi”',
            'lugar' => $valor('solicitud_lugar') ?: 'Tegucigalpa, M.D.C.',
            'fecha' => $hoy->day.' de '.self::MESES[$hoy->month - 1].' de '.$hoy->year,
            'destinatario_tratamiento' => mb_strtoupper($valor('destinatario_tratamiento')),
            'destinatario_nombre' => mb_strtoupper($valor('destinatario_nombre')),
            'destinatario_cargo' => mb_strtoupper($valor('destinatario_cargo')),
            'institucion' => mb_strtoupper($valor('nombre_institucion')),
            'saludo' => TratamientoDestinatario::saludo($valor('destinatario_tratamiento')),
            'carrera' => $valor('carrera'),
            'practica' => $servicioSocial ? 'el servicio social' : 'la práctica profesional supervisada',
            'horas' => (string) (int) $valor('total_horas'),
            'modalidad' => match ($valor('modalidad_ejecucion')) {
                '100% presencial' => 'presencial',
                'Híbrida' => 'híbrida (presencial y teletrabajo)',
                'Teletrabajo' => 'de teletrabajo',
                default => mb_strtolower($valor('modalidad_ejecucion')),
            },
            'estudiante' => mb_strtoupper($valor('nombre_estudiante')),
            'cuenta' => $valor('numero_cuenta'),
            'firmante_nombre' => $firmante['nombre'],
            'firmante_cargo' => $firmante['cargo'],
            'carrera_centro' => collect([$valor('carrera'), $valor('facultad_centro')])->filter()->implode(', '),
            'pie_institucional' => $this->pieInstitucional($valor('facultad_centro')),
        ];
    }

    /** «Universidad Nacional Autónoma de Honduras | CURLP | Choluteca, … | www.curlp.unah.edu.hn». */
    private function pieInstitucional(string $facultadCentro): string
    {
        $campus = FacultadCentro::with('campus')->where('nombre', $facultadCentro)->first()?->campus;

        if (! $campus) {
            return 'Universidad Nacional Autónoma de Honduras | CU | Tegucigalpa M.D.C., Honduras C.A. | www.unah.edu.hn';
        }

        $sitio = trim((string) preg_replace('#^https?://|/+$#', '', (string) $campus->url));

        return collect([
            'Universidad Nacional Autónoma de Honduras',
            $campus->siglas,
            config('documents.ubicacion_campus.'.$campus->siglas) ?? rtrim((string) $campus->direccion, '. '),
            $sitio === '' || str_starts_with($sitio, 'www.') ? $sitio : 'www.'.$sitio,
        ])->filter()->implode(' | ');
    }

    /** @return list<string> */
    private function partesConTexto(string $docx): array
    {
        $zip = new ZipArchive;
        if ($zip->open($docx) !== true) {
            throw new RuntimeException('No se pudo abrir la SOLICITUD DE PRÁCTICA generada.');
        }

        $partes = [];
        for ($indice = 0; $indice < $zip->numFiles; $indice++) {
            $nombre = (string) $zip->getNameIndex($indice);
            if (preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $nombre)) {
                $partes[] = $nombre;
            }
        }
        $zip->close();

        return $partes;
    }
}
