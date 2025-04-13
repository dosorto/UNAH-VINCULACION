<?php

namespace App\Services\Constancia;

use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Blade;

Carbon::setLocale('es');



class BuilderConstancia
{
    private Constancia $constancia;
    private EmpleadoProyecto $empleadoProyecto;
    private Empleado $empleado;
    private Proyecto $proyecto;
    private Empleado $director;

    private array $data = [];
    private string $tipo;
    private bool $pdf;
    private string|null $qrPath = null;

    public function __construct(
        EmpleadoProyecto $empleadoProyecto,
        string $tipo,
        bool $pdf = false
    ) {
        $this->empleadoProyecto = $empleadoProyecto;
        $this->tipo = $tipo;
        $this->pdf = $pdf;

        $this->empleado = $empleadoProyecto->empleado;
        $this->proyecto = $empleadoProyecto->proyecto;
        $this->director = $this->proyecto->director_vinculacion;

        $this->constancia = $this->resolveConstancia();

        // Generar el QR antes de construir los datos
        $this->generateQRCode();

        $this->data = $this->buildData();
    }

    public static function make(EmpleadoProyecto $empleadoProyecto, string $tipo, bool $pdf): self
    {
        return new static($empleadoProyecto, $tipo, $pdf);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getConstancia()
    {
        return MakeConstancia::make($this->constancia)
            ->setLayout("app.docente.constancias.constancia_finalizado")
            ->setData($this->data)
            ->generate()
            ->downloadPDF();
    }

    private function resolveConstancia(): ?Constancia
    {
        $tipoId = TipoConstancia::where('nombre', $this->tipo)->value('id');

        return Constancia::where([
            ['origen_type', Proyecto::class],
            ['origen_id', $this->empleadoProyecto->proyecto_id],
            ['destinatario_type', Empleado::class],
            ['destinatario_id', $this->empleadoProyecto->empleado_id],
            ['tipo_constancia_id', $tipoId],
        ])->first();
    }

    private function formatFecha(?string $fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->translatedFormat('j \d\e F \d\e Y') : 'Fecha no disponible';
    }

    private function buildData(): array
    {
        $empleado = $this->empleado?->nombre_completo ?? 'Nombre del Empleado';
        $numeroEmpleado = $this->empleado?->numero_empleado ?? 'N° empleado';
        $nombreCentro = $this->empleado?->centro_facultad->nombre ?? 'Nombre del Centro';
        $nombreDepartamento = $this->empleado?->departamento_academico->nombre ?? 'Nombre del Departamento';
        $director = $this->director?->nombre_completo ?? 'Nombre del Director';
        $nombreProyecto = $this->proyecto?->nombre_proyecto ?? 'Nombre del Proyecto';

        $fechaInicio = $this->formatFecha($this->proyecto?->fecha_inicio);
        $fechaFinal = $this->formatFecha($this->proyecto?->fecha_finalizacion);

        $data = [
            'titulo' => $this->buildTitulo(),
            'texto' => $this->buildTexto(
                $empleado,
                $numeroEmpleado,
                $nombreDepartamento,
                $nombreCentro,
                $nombreProyecto,
                $fechaInicio,
                $fechaFinal,
                $director
            ),
            'nombreDirector' => $director,
            'firmaDirector' => $this->director?->firma?->ruta_storage ?? '',
            'selloDirector' => $this->director?->sello?->ruta_storage ?? '',
            'anioAcademico' => 'Año Académico 2025 "José Dionisio de Herrera"',
            'codigoVerificacion' => $this->constancia?->hash ?? '',
            'pdf' => $this->pdf,
            'qrCode' => $this->qrPath,
        ];

        return array_merge($data, $this->getImagePaths($data));
    }

    private function buildTitulo(): string
    {
        return match ($this->tipo) {
            'inscripcion' => 'CONSTANCIA DE ACTIVIDAD DE VINCULACIÓN',
            'finalizacion' => 'CONSTANCIA DE FINALIZACIÓN DE ACTIVIDAD DE VINCULACIÓN',
            default => 'N/D',
        };
    }


    private function buildTexto(
        $empleado,
        $numeroEmpleado,
        $nombreDepartamento,
        $nombreCentro,
        $nombreProyecto,
        $fechaInicio,
        $fechaFinal,
        $director
    ): string {
        $data = compact(
            'empleado',
            'numeroEmpleado',
            'nombreDepartamento',
            'nombreCentro',
            'nombreProyecto',
            'fechaInicio',
            'fechaFinal',
            'director'
        );
    
        return match ($this->tipo) {
            'inscripcion' => Blade::render("
                <p>
                    La suscrita Directora de Vinculación Universidad-Sociedad-VRA-UNAH,
                    por este medio hace constar que el empleado <strong>{{ \$empleado }}</strong> con número de empleado 
                    <strong>{{ \$numeroEmpleado }}</strong>, del departamento de <strong>{{ \$nombreDepartamento }}</strong>, dependiente de 
                    <strong>{{ \$nombreCentro }}</strong>, forma parte del equipo que ejecuta la actividad de vinculación 
                    denominada <strong>{{ \$nombreProyecto }}</strong>, la cual se desarrolla del <strong>{{ \$fechaInicio }}</strong> hasta el 
                    <strong>{{ \$fechaFinal }}</strong>. Asimismo, se hace constar que la entrega del informe intermedio y final 
                    de dicha actividad está pendiente. En consecuencia, esta constancia tiene validez únicamente 
                    para fines de inscripción en la Dirección de Vinculación Universidad – Sociedad.
                </p>
            ", $data),
    
            'finalizacion' => Blade::render("
                El suscrito Director de Vinculación, <strong>{{ \$director }}</strong>, hace constar que el empleado 
                <strong>{{ \$empleado }}</strong>, con número de empleado <strong>{{ \$numeroEmpleado }}</strong>, ha concluido satisfactoriamente 
                su participación en la actividad de vinculación <strong>{{ \$nombreProyecto }}</strong> desarrollada 
                del <strong>{{ \$fechaInicio }}</strong> al <strong>{{ \$fechaFinal }}</strong>.
            ", $data),
    
            default => 'N/D',
        };
    }
    
    private function getImagePaths(array $data): array
    {
        $codigo = $data['codigoVerificacion'] ?? '';
        $firmaDirector = $data['firmaDirector'] ?? '';
        $selloDirector = $data['selloDirector'] ?? '';

        $filePrefix = $this->pdf ? 'file://' : '';

        return [
            'solImage' => $this->pdf
                ? public_path('/images/Image/Sol.png')
                : asset('images/Image/Sol.png'),

            'logoImage' => $this->pdf
                ? $filePrefix . public_path('images/imagenvinculacion.jpg')
                : asset('images/imagenvinculacion.jpg'),

            'firmaImage' => $this->pdf
                ? public_path('f.png')
                : asset('f.png'),

            'qrCode' => $this->qrPath,

            'firmaDirector' => $this->pdf
                ? $filePrefix . storage_path("app/public/{$firmaDirector}")
                : Storage::url($firmaDirector),

            'selloDirector' => $this->pdf
                ? $filePrefix . storage_path("app/public/{$selloDirector}")
                : Storage::url($selloDirector),
        ];
    }

    private function generateQRCode(): void
    {
        $hash = $this->constancia?->hash ?? '';
        $enlace = route('verificacion_constancia', ['hash' => $hash]);

        if ($this->pdf) {
            // Guardar el archivo como imagen
            $qrCodeName = $hash . '.png';
            $qrcodePath = storage_path("app/public/{$qrCodeName}");

            QrCode::format('png')
                ->size(200)
                ->errorCorrection('H')
                ->generate($enlace, $qrcodePath);

            $this->qrPath = 'file://' . $qrcodePath;
        } else {
            // Generar base64 para evitar guardar archivo
            $qrBase64 = base64_encode(
                QrCode::format('png')
                    ->size(200)
                    ->errorCorrection('H')
                    ->generate($enlace)
            );

            $this->qrPath = 'data:image/png;base64,' . $qrBase64;
        }
    }
}
