<?php

namespace App\Services\PpsServicioSocial;

use App\Models\PpsServicioSocial;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\LibreOfficePdfConverter;
use App\Support\PpsServicioSocial\FormDvus014Data;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Cartas del FORM-DVUS-014 (solicitud de práctica y autorización de PPS): llenan su plantilla de
 * Word (marcadores {{...}}) y la convierten a PDF con LibreOffice, igual que el FORM-DVUS-018.
 * Comparten el encabezado institucional, el pie del campus y el bloque de firma de quien llena
 * el formulario.
 */
abstract class PpsCartaDocumento
{
    // Caja de la firma en EMU (1 cm = 360000): hasta 6 × 1,8 cm, sin deformarla.
    private const FIRMA_ANCHO = 2160000;
    private const FIRMA_ALTO = 648000;

    protected const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function __construct(private readonly LibreOfficePdfConverter $converter) {}

    /** Ruta de la plantilla de Word. */
    abstract protected function plantilla(): string;

    /** Nombre de la carta en los mensajes de error («la SOLICITUD DE PRÁCTICA»). */
    abstract protected function documento(): string;

    /**
     * Marcadores propios de la carta.
     *
     * @param  array<string, mixed>  $campos  campos de FormDvus014Data
     * @param  array{nombre: string, cargo: string, sexo?: ?string, firma?: ?string}  $firmante
     * @return array<string, string>
     */
    abstract protected function valoresPropios(array $campos, array $firmante): array;

    /**
     * @param  array{nombre: string, cargo: string, sexo?: ?string, firma?: ?string}  $firmante  firma: imagen PNG o JPEG
     * @return string contenido del PDF
     */
    public function pdf(PpsServicioSocial $pps, array $firmante): string
    {
        $plantilla = $this->plantilla();
        if (! is_file($plantilla) || ! is_readable($plantilla)) {
            throw new RuntimeException('No se encontró la plantilla de '.$this->documento().'. Ruta: '.$plantilla);
        }

        $directorio = storage_path('app/tmp/cartas-pps/'.Str::random(32));
        File::ensureDirectoryExists($directorio);

        try {
            $docx = $directorio.'/carta.docx';
            if (! copy($plantilla, $docx)) {
                throw new RuntimeException('No se pudo copiar la plantilla de '.$this->documento().'.');
            }

            $this->llenar($docx, $pps, $firmante);

            return (string) file_get_contents($this->converter->convert($docx, $directorio, $this->documento()));
        } finally {
            File::deleteDirectory($directorio);
        }
    }

    /**
     * Llena los marcadores del cuerpo, los encabezados y los pies, y pone la firma registrada
     * de quien firma; sin ella queda el espacio para firmar a mano.
     *
     * @param  array{nombre: string, cargo: string, sexo?: ?string, firma?: ?string}  $firmante
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
     * @param  array{nombre: string, cargo: string, sexo?: ?string, firma?: ?string}  $firmante
     * @return array<string, string>
     */
    public function valores(PpsServicioSocial $pps, array $firmante): array
    {
        $campos = FormDvus014Data::from($pps)['fields'];
        $hoy = Carbon::now();

        return array_merge([
            'lema' => '“Año Académico '.$hoy->year.' María Elena Bottazzi”',
            'lugar' => self::texto($campos, 'solicitud_lugar') ?: 'Tegucigalpa, M.D.C.',
            'fecha' => self::fechaLarga($hoy),
            'carrera' => self::texto($campos, 'carrera'),
            'horas' => (string) (int) self::texto($campos, 'total_horas'),
            'estudiante' => mb_strtoupper(self::texto($campos, 'nombre_estudiante')),
            'cuenta' => self::texto($campos, 'numero_cuenta'),
            'firmante_nombre' => $firmante['nombre'],
            'firmante_cargo' => $firmante['cargo'],
            'carrera_centro' => collect([self::texto($campos, 'carrera'), self::texto($campos, 'facultad_centro')])->filter()->implode(', '),
            'pie_institucional' => $this->pieInstitucional(self::texto($campos, 'facultad_centro')),
        ], $this->valoresPropios($campos, $firmante));
    }

    /** @param  array<string, mixed>  $campos */
    protected static function texto(array $campos, string $campo): string
    {
        return trim((string) ($campos[$campo] ?? ''));
    }

    /** @param  array<string, mixed>  $campos */
    protected static function esServicioSocial(array $campos): bool
    {
        return Str::contains(Str::lower(Str::ascii(self::texto($campos, 'tipo_pps_ss'))), 'servicio');
    }

    /** «29 de septiembre de 2026», o «… del año 2026» con $delAnio. */
    protected static function fechaLarga(\DateTimeInterface $fecha, bool $delAnio = false): string
    {
        $fecha = Carbon::instance($fecha);

        return $fecha->day.' de '.self::MESES[$fecha->month - 1].($delAnio ? ' del año ' : ' de ').$fecha->year;
    }

    protected function campus(string $facultadCentro): ?Campus
    {
        return $facultadCentro === ''
            ? null
            : FacultadCentro::with('campus')->where('nombre', $facultadCentro)->first()?->campus;
    }

    /** «Universidad Nacional Autónoma de Honduras | CURLP | Choluteca, … | www.curlp.unah.edu.hn». */
    private function pieInstitucional(string $facultadCentro): string
    {
        $campus = $this->campus($facultadCentro);

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
            throw new RuntimeException('No se pudo abrir '.$this->documento().' generada.');
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
