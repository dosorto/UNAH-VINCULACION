<?php

namespace App\Services\Constancia;

use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\Estudiante\Estudiante;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;

use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\IntegranteInternacionalProyecto;
use App\Models\Presupuesto\Presupuesto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

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

    private function generarCorrelativo(): string
    {
        $anio = now()->year;
        $prefijo = 'VRA-DVUS';
        $siglasCentro = $this->proyecto->coordinador->centro_facultad->siglas ?? 'N/A';
        $maxIntentos = 100;

        do {
            $numero = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $correlativo = "$prefijo-$siglasCentro-$numero/$anio";
            
            if (!Constancia::where('correlativo', $correlativo)->exists()) {
                return $correlativo;
            }
        } while (--$maxIntentos > 0);

        throw new \Exception("No se generó correlativo único después de 100 intentos");
    }

    public function getConstancia()
    {
        $layout = match ($this->tipo) {
            'actualizacion' => 'app.docente.constancias.constancia_actualizacion',
            'finalizacion' => 'app.docente.constancias.constancia_finalizacion',
            default => 'app.docente.constancias.constancia_registro',
        };

        return MakeConstancia::make($this->constancia)
            ->setLayout($layout)
            ->setData($this->data)
            ->generate()
            ->downloadPDF();
    }

    private function resolveConstancia(): ?Constancia
    {
        $tipoId = TipoConstancia::where('nombre', $this->tipo)->value('id');
        if (!$tipoId) return null;

        $constancia = Constancia::where([
            ['origen_type', Proyecto::class],
            ['origen_id', $this->empleadoProyecto->proyecto_id],
            ['destinatario_type', Empleado::class],
            ['destinatario_id', $this->empleadoProyecto->empleado_id],
            ['tipo_constancia_id', $tipoId] 
        ])->first();

        if ($constancia && empty($constancia->correlativo)) {
            $constancia->update(['correlativo' => $this->generarCorrelativo()]);
            $constancia->refresh();
        }

        if (!$constancia) {
            $constancia = Constancia::create([
                'origen_type' => Proyecto::class,
                'origen_id' => $this->empleadoProyecto->proyecto_id,
                'destinatario_type' => Empleado::class,
                'destinatario_id' => $this->empleadoProyecto->empleado_id,
                'tipo_constancia_id' => $tipoId,
                'status' => true,
                'correlativo' => $this->generarCorrelativo(),
                'hash' => Str::random(10)
            ]);
        }

        return $constancia;
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
        $categoria = $this->empleado?->categoria?->nombre ?? 'Categoría no asignada';
        $codigoProyecto = $this->proyecto?->codigo_proyecto ?? 'Código no disponible';
        $rol = $this->empleadoProyecto->rol ?? 'Rol no asignado';
        $departamentos = $this->proyecto->departamento->pluck('nombre')->implode(', ') ?: 'No especificado';
        $municipios = $this->proyecto->municipio->pluck('nombre')->implode(', ') ?: 'No especificado';

        $unidadAcademica = $this->proyecto?->unidad_academica ?? 'No especificada';
        $beneficiarios = $this->proyecto?->poblacion_participante ?? 'No especificado';

        $presupuesto = $this->proyecto->presupuesto;
        $totalAporteInstitucional = $this->proyecto->aportesInstitucionales()->sum('costo_total');
        $aporteContraparte = floatval($presupuesto->aporte_contraparte ?? 0);
        $aporteInternacionales = floatval($presupuesto->aporte_internacionales ?? 0);
        $aporteOtrasUniversidades = floatval($presupuesto->aporte_otras_universidades ?? 0);
        $aporteComunidad = floatval($presupuesto->aporte_comunidad ?? 0);
        $otrosAportes = floatval($presupuesto->otros_aportes ?? 0);

        $superavitTotal = 0;
        if (!empty($presupuesto->superavit) && is_iterable($presupuesto->superavit)) {
            foreach ($presupuesto->superavit as $item) {
                $superavitTotal += floatval(is_array($item) ? ($item['monto'] ?? 0) : ($item->monto ?? 0));
            }
        }

        $presupuestoEjecutado = $totalAporteInstitucional
            + $aporteContraparte
            + $aporteInternacionales
            + $aporteOtrasUniversidades
            + $aporteComunidad
            + $otrosAportes
            + $superavitTotal;

        $presupuestoEjecutado = number_format($presupuestoEjecutado, 2, '.', ',');

        $fechaInformeIntermedio = optional(
            $this->proyecto->documentos()->where('tipo_documento', 'Informe Intermedio')->latest()->first()
        )?->created_at?->format('d \d\e F \d\e Y') ?? 'No disponible';
        $fechaInformeFinal = optional(
            $this->proyecto->documentos()->where('tipo_documento', 'Informe Final')->latest()->first()
        )?->created_at?->format('d \d\e F \d\e Y') ?? 'No disponible';

        $headerLogo = $this->getValidLogoPath(env('PDF_HEADER_LOGO'));
        $footerLogo = $this->getValidLogoPath(env('PDF_FOOTER_LOGO'));
        $solLogo = $this->getValidLogoPath(env('PDF_SOL_LOGO'));
        $lucenLogo = $this->getValidLogoPath(env('PDF_LUCEN_LOGO'));

        $integrantes = $this->proyecto->docentes_proyecto()
            ->with([
                'empleado.departamento_academico',
                'empleado.categoria'
            ])
            ->get()
            ->map(function ($empleadoProyecto, $index) {
                $emp = $empleadoProyecto->empleado;

                return [
                    'no' => $index + 1,
                    'nombre_completo' => $emp->nombre_completo,
                    'numero_empleado' => $emp->numero_empleado,
                    'categoria' => $emp->categoria?->nombre ?? 'No asignada',
                    'departamento' => $emp->departamento_academico?->nombre ?? 'No asignado',
                    'rol' => $empleadoProyecto->rol ?? 'No especificado',
                ];
            })->toArray();

            $integrantesInternacionales = $this->proyecto->integrantesInternacionales->map(function ($item, $index) {
                return [
                    'no' => $index + 1,
                    'nombre_completo' => $item->nombre_completo,
                    'institucion' => $item->institucion,
                    'rol' => 'Integrante internacional',
                ];
            })->toArray();

            // Estudiantes
            $estudiantes = $this->proyecto->participacionesEstudiantes->map(function ($item, $index) {
                $est = $item->estudiante;
                return [
                    'no' => $index + 1,
                    'nombre_completo' => $est->nombre . ' ' . $est->apellido,
                    'cuenta' => $est->cuenta,
                    'tipo_participacion' => $item->tipoParticipacion?->nombre ?? 'No especificado',
                    'departamento' => $emp->departamento_academico?->nombre ?? 'No asignado',
                ];
            })->toArray();

        $firmaDirector = $this->getValidDirectorAsset($this->director?->firma?->ruta_storage, 'firma');
        $selloDirector = $this->getValidDirectorAsset($this->director?->sello?->ruta_storage, 'sello');

        $fechaInicio = $this->formatFecha($this->proyecto?->fecha_inicio);
        $fechaFinal = $this->formatFecha($this->proyecto?->fecha_finalizacion);

        $data = [
            'titulo' => $this->buildTitulo(),
            'nombreEmpleado' => $empleado,
            'numeroEmpleado' => $numeroEmpleado,
            'nombreProyecto' => $nombreProyecto,
            'headerLogo' => $headerLogo,
            'footerLogo' => $footerLogo,
            'solLogo' => $solLogo,
            'lucenLogo' => $lucenLogo,
            'categoria' => $categoria,
            'rol' => $rol,
            'codigoProyecto' => $codigoProyecto,
            'departamentos' => $departamentos,
            'municipios' => $municipios,
            'integrantes' => $integrantes,
            'integrantes_internacionales' => $integrantesInternacionales,
            'estudiantes' => $estudiantes,
            'tipo' => $this->tipo,
            'horas' => 0,
            'texto' => $this->buildTexto(
                $empleado,
                $numeroEmpleado,
                $nombreDepartamento,
                $nombreCentro,
                $nombreProyecto,
                $fechaInicio,
                $fechaFinal,
                $director,
                $categoria,
                $rol,
                $codigoProyecto,
                $departamentos,
                $municipios,
                $integrantes
            ),
            'nombreDepartamento' => $nombreDepartamento,
            'nombreDirector' => $director,
            'firmaDirector' => $firmaDirector,
            'selloDirector' => $selloDirector,
            'anioAcademico' => 'Año Académico 2025 "José Dionisio de Herrera"',
            'codigoVerificacion' => $this->constancia?->hash ?? '',
            'correlativo' => $this->constancia->correlativo ?? 'DVUS-CONST-0001/2025',
            'pdf' => $this->pdf,
            'qrCode' => $this->qrPath,
            'unidadAcademica' => $unidadAcademica,
            'beneficiarios' => $beneficiarios,
            'presupuestoEjecutado' => $presupuestoEjecutado,
            'fechaInformeIntermedio' => $fechaInformeIntermedio,
            'fechaInformeFinal' => $fechaInformeFinal,
            'fechaInicio' => $fechaInicio,
            'fechaFinal' => $fechaFinal,
        ];

        return $data;
    }

    private function getValidLogoPath(?string $logoPath): string
    {
        if (empty($logoPath)) {
            return $this->pdf ? 'file://' . public_path('images/default-logo.png') : asset('images/default-logo.png');
        }

        $fullPath = public_path($logoPath);
        
        if ($this->pdf) {
            return 'file://' . $fullPath;
        }
        
        return asset($logoPath);
    }

    private function getValidDirectorAsset(?string $storagePath, string $type): string
    {
        if (empty($storagePath)) {
            return $this->pdf ? 'file://' . public_path("images/default-{$type}.png") : asset("images/default-{$type}.png");
        }

        $fullPath = storage_path("app/public/{$storagePath}");
        
        if (is_dir($fullPath) || !file_exists($fullPath)) {
            return $this->pdf ? 'file://' . public_path("images/default-{$type}.png") : asset("images/default-{$type}.png");
        }

        if ($this->pdf) {
            return 'file://' . $fullPath;
        }
        
        return Storage::url($storagePath);
    }

    private function buildTitulo(): string
    {
        return match ($this->tipo) {
            'inscripcion' => 'CONSTANCIA DE REGISTRO DE PROYECTO DE VINCULACIÓN',
            'finalizacion' => 'CONSTANCIA DE FINALIZACIÓN DE PROYECTO DE VINCULACIÓN',
            'actualizacion' => 'CONSTANCIA DE ACTUALIZACIÓN DE PROYECTO DE VINCULACIÓN',
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
        $director,
        $categoria,
        $rolDocente,
        $codigoProyecto,
        $departamentos,
        $municipios,
        $integrantes
    ): string {
        return match ($this->tipo) {
            'inscripcion' => Blade::render(<<<BLADE
                <p>
                    El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace CONSTAR que 
                    <strong>{{ \$nombreCentro }}</strong> ha registrado el proyecto de vinculación denominado 
                    <strong>{{ \$nombreProyecto }}</strong>, el cual se ejecuta en {{ \$municipios }}, Departamentos 
                    {{ \$departamentos }} durante el período {{ \$fechaInicio }} hasta el 
                    {{ \$fechaFinal }}, el cual fue registrado en esta dirección con el número 
                    <strong>{{ \$codigoProyecto }}</strong>. El equipo docente responsable de la ejecución de este proyecto es el siguiente:
                </p>
            BLADE, compact(
                'nombreCentro',
                'nombreProyecto',
                'departamentos',
                'municipios',
                'fechaInicio',
                'fechaFinal',
                'codigoProyecto'
            )),

            'actualizacion' => Blade::render(<<<BLADE
                <p>
                    El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace CONSTAR que 
                    <strong>$nombreCentro </strong> ha registrado el proyecto de vinculación denominado 
                    <strong>{{ \$nombreProyecto }}</strong>, el cual se ejecuta en los municipios de 
                    <strong>{{ \$municipios }}</strong> durante el período 
                    <strong>{{ \$fechaInicio }}</strong> hasta el <strong>{{ \$fechaFinal }}</strong>, 
                    el cual fue registrado en esta dirección con el número 
                    <strong>{{ \$codigoProyecto }}</strong>. A la fecha, el equipo docente responsable de la ejecución de este proyecto es el siguiente:
                </p>
            BLADE, compact(
                'nombreProyecto',
                'municipios',
                'fechaInicio',
                'fechaFinal',
                'codigoProyecto'
            )),

         'finalizacion' => Blade::render(<<<BLADE
                <p>
                    El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace <strong>CONSTAR</strong> que el proyecto
                    de vinculación denominado: <strong>{{ \$nombreProyecto }}</strong>, con código de registro <strong>{{ \$codigoProyecto }}</strong> 
                    ha presentado el informe final que detalla los siguientes datos:
                </p>
            BLADE, [
                'codigoProyecto' => $codigoProyecto,
                'nombreProyecto' => $nombreProyecto,
            ]),

            default => '<p>N/D</p>',
        };
    }

    private function generateQRCode(): void
    {
        $hash = $this->constancia?->hash ?? '';
        $enlace = route('verificacion_constancia', ['hash' => $hash]);

        if ($this->pdf) {
            $qrCodeName = $hash . '.png';
            $qrcodePath = storage_path("app/public/{$qrCodeName}");

            QrCode::format('png')
                ->size(150)
                ->errorCorrection('L')
                ->generate($enlace, $qrcodePath);

            $this->qrPath = 'file://' . $qrcodePath;
        } else {
            $qrBase64 = base64_encode(
                QrCode::format('png')
                    ->size(150)
                    ->errorCorrection('L')
                    ->generate($enlace)
            );

            $this->qrPath = 'data:image/png;base64,' . $qrBase64;
        }
    }
}
