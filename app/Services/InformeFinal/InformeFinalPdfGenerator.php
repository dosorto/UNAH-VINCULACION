<?php

namespace App\Services\InformeFinal;

use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\FirmaProyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InformeFinalPdfGenerator
{
    public function make(InformeFinalProyecto $informe)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $pdf = Pdf::loadView('pdf.informes-finales.inf-001', $this->viewData($informe, true))
            ->setPaper([0, 0, 612, 792])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'Arial')
            ->setOption('dpi', 96)
            ->setOption('chroot', realpath(base_path()));
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('Arial', 'normal');
        $dompdf->getCanvas()->page_text(505, 760, '{PAGE_NUM}', $font, 7, [0, 0.125, 0.375]);

        return $pdf;
    }

    public function content(InformeFinalProyecto $informe): string
    {
        return $this->make($informe)->output();
    }

    public function viewData(InformeFinalProyecto $informe, bool $isPdf): array
    {
        $informe = $this->load($informe);
        $firmas = [
            'coordinador' => null,
            'jefe' => null,
            'enlace' => null,
            'decano' => null,
        ];
        // Los 4 cuadros del INF-001 se resuelven contra `tipo_cargo_firma.nombre` (fuente
        // canónica: Proyecto::firma_proyecto_jefe/enlace/decano y firma_coodinador_proyecto).
        // Las variantes de texto quedan como respaldo para `etapa_nombre`/`rol_requerido`.
        $cargos = [
            'coordinador proyecto' => 'coordinador',
            'coordinador del proyecto' => 'coordinador',
            'jefe departamento' => 'jefe',
            'jefe de departamento' => 'jefe',
            'jefe de la unidad académica' => 'jefe',
            'enlace vinculacion' => 'enlace',
            'enlace vinculación' => 'enlace',
            'coordinador comité vinculación' => 'enlace',
            'coordinador(a) del comité de vinculación' => 'enlace',
            'director centro' => 'decano',
            'director de centro' => 'decano',
            'director del centro regional' => 'decano',
            'decano' => 'decano',
            'decano(a) o director(a) del centro regional' => 'decano',
        ];

        $documento = $informe->documentoCierre;
        $firmasCiclo = collect();

        if ($documento) {
            $ultimoCiclo = (int) $documento->firma_documento()
                ->whereNotNull('flujo_aprobacion_etapa_id')
                ->max('revision_ciclo');

            $firmasCiclo = $documento->firma_documento
                ->where('revision_ciclo', $ultimoCiclo)
                ->filter(fn (FirmaProyecto $firma) => filled($firma->flujo_aprobacion_etapa_id))
                ->sortBy([
                    ['fecha_firma', 'asc'],
                    ['updated_at', 'asc'],
                    ['id', 'asc'],
                ])
                ->groupBy('flujo_aprobacion_etapa_id')
                ->map(fn ($decisiones) => $decisiones->last())
                ->filter(fn (FirmaProyecto $firma) => $firma->estado_revision === 'Aprobado')
                ->sortBy('orden_revision')
                ->values();
        }

        foreach ($firmasCiclo as $firma) {
            $slot = collect([
                $firma->cargo_firma?->tipoCargoFirma?->nombre,
                $firma->rol_requerido,
                $firma->etapa_nombre,
            ])->filter()
                ->map(fn ($valor) => mb_strtolower(trim((string) $valor)))
                ->map(fn ($valor) => $cargos[$valor] ?? null)
                ->filter()
                ->first();

            if (! $slot) {
                continue;
            }

            $firmas[$slot] = $this->firmaData($firma, $isPdf);
        }

        $coordinadorProyecto = $informe->proyecto->coordinador_proyecto->first()?->empleado;
        $esBorrador = $informe->estadoFlujo() !== InformeFinalProyecto::ESTADO_APROBADO;

        return compact('informe', 'firmas', 'coordinadorProyecto', 'esBorrador');
    }

    public function filename(InformeFinalProyecto $informe): string
    {
        $identificador = $informe->numero_registro ?: 'Pendiente-de-asignacion';
        $identificador = preg_replace('/[\/\\\\:\*\?"<>\|]+/', '', (string) $identificador);
        $identificador = preg_replace('/\s+/', '-', trim((string) $identificador));
        $identificador = preg_replace('/\.pdf$/i', '', (string) $identificador);
        $identificador = trim((string) $identificador, '-.');

        return 'INF-001-'.($identificador ?: 'Pendiente-de-asignacion').'.pdf';
    }

    private function firmaData(FirmaProyecto $firma, bool $isPdf): array
    {
        return [
            'nombre' => $firma->empleado?->nombre_completo,
            'firma' => $this->resolverRutaFirma($firma->firma?->ruta_storage, $isPdf),
            'sello' => $this->resolverRutaFirma($firma->sello?->ruta_storage, $isPdf),
            'fecha' => $firma->fecha_firma,
            'cargo' => $firma->etapa_nombre ?: $firma->cargo_firma?->tipoCargoFirma?->nombre,
        ];
    }

    private function resolverRutaFirma(?string $ruta, bool $isPdf): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return $ruta;
        }

        $rutaNormalizada = ltrim((string) $ruta, '/');
        if (str_starts_with($rutaNormalizada, 'storage/')) {
            $rutaNormalizada = substr($rutaNormalizada, strlen('storage/'));
        }

        $rutaPublica = public_path('storage/'.$rutaNormalizada);
        $rutaDisco = storage_path('app/public/'.$rutaNormalizada);

        if (is_file($rutaDisco) || Storage::disk('public')->exists($rutaNormalizada)) {
            return $isPdf ? 'file://'.$rutaDisco : Storage::url($rutaNormalizada);
        }

        if (is_file($rutaPublica)) {
            return $isPdf ? 'file://'.$rutaPublica : Storage::url($rutaNormalizada);
        }

        return null;
    }

    private function load(InformeFinalProyecto $informe): InformeFinalProyecto
    {
        return $informe->loadMissing([
            'proyecto.objetivosEspecificos',
            'proyecto.coordinador_proyecto.empleado',
            'documentoCierre.estadoActual.tipoestado',
            'documentoCierre.firma_documento.empleado',
            'documentoCierre.firma_documento.firma',
            'documentoCierre.firma_documento.sello',
            'documentoCierre.firma_documento.cargo_firma.tipoCargoFirma',
            'beneficiarios',
            'equipoDocente',
            'cooperacion',
            'gruposEstudiantes.asignatura',
            'estudiantes.grupo.asignatura',
            'voluntarios',
            'contrapartes',
            'resultados',
            'actividades.participantes',
            'accionesNoEjecutadas',
            'accionesEmergentes.resultado',
            'ods.ods',
            'ods.meta',
            'presupuestoDetalles',
            'anexos.contraparte',
        ]);
    }
}
