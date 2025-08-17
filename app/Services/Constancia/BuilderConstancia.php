<?php

namespace App\Services\Constancia;

use App\Models\Constancia\Constancia;
use App\Models\Constancia\TipoConstancia;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\EmpleadoCodigoInvestigacion;
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

    private function generarCorrelativo(): string
{
    $anio = now()->year;
    $prefijo = 'DVUS-CONST';

    // Buscar el último correlativo del año, ignorando mayúsculas y con formato correcto
    $ultimaConstancia = Constancia::whereRaw('UPPER(correlativo) LIKE ?', ["{$prefijo}-%/{$anio}"])
        ->orderBy('id', 'desc')
        ->first();

    if ($ultimaConstancia) {
        // Extraer el número: DVUS-CONST-0068/2025
        preg_match('/-(\d{4})\/\d+$/', $ultimaConstancia->correlativo, $matches);
        $ultimoNumero = $matches[1] ?? '0001';
        $nuevoNumero = intval($ultimoNumero) + 1;
    } else {
        $nuevoNumero = 1;
    }

    // Formato con 4 dígitos: 0001, 0002, ..., 0068
    $numeroFormateado = str_pad($nuevoNumero, 4, '0', STR_PAD_LEFT);

    return "$prefijo-$numeroFormateado/$anio";
}

    public function getConstancia()
    {
        $layout = match ($this->tipo) {
            'actualizacion' => 'app.docente.constancias.constancia_actualizacion',
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

        if (!$tipoId) {
            return null;
        }

        $conditions = [
            ['origen_type', Proyecto::class],
            ['origen_id', $this->empleadoProyecto->proyecto_id],
            ['destinatario_type', Empleado::class],
            ['destinatario_id', $this->empleadoProyecto->empleado_id],
            ['tipo_constancia_id', $tipoId],
        ];

        // Buscar si ya existe
        $constancia = Constancia::where($conditions)->first();

        // Si no existe, crearla
        if (!$constancia) {
            $constancia = Constancia::create([
                'origen_type' => Proyecto::class,
                'origen_id' => $this->empleadoProyecto->proyecto_id,
                'destinatario_type' => Empleado::class,
                'destinatario_id' => $this->empleadoProyecto->empleado_id,
                'tipo_constancia_id' => $tipoId,
                'status' => 'generada', // o el estado que uses
                'hash' => Str::random(32),
                'correlativo' => $this->generarCorrelativo(),
                'validaciones' => json_encode([]), // si es necesario
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
    $registroInvestigacion = EmpleadoCodigoInvestigacion::where('empleado_id', $this->empleado->id)
        ->where('codigo_proyecto', $codigoProyecto)
        ->first();
    $rol = $this->empleadoProyecto->rol ?? 'Rol no asignado';
    $departamentos = $this->proyecto->departamento->pluck('nombre')->implode(', ') ?: 'No especificado';
    $municipios = $this->proyecto->municipio->pluck('nombre')->implode(', ') ?: 'No especificado';
    


    // Obtener rutas desde .env
    $headerLogo = env('PDF_HEADER_LOGO');
    $footerLogo = env('PDF_FOOTER_LOGO');
    $solLogo    = env('PDF_SOL_LOGO');
    $lucenLogo  = env('PDF_LUCEN_LOGO');


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

    // Si estamos generando PDF, convertir rutas a file:// 
    if ($this->pdf) {
        $headerLogo = 'file://' . public_path($headerLogo);
        $footerLogo = 'file://' . public_path($footerLogo);
        $solLogo    = 'file://' . public_path($solLogo);
        $lucenLogo  = 'file://' . public_path($lucenLogo);
    }

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
        'firmaDirector' => $this->director?->firma?->ruta_storage ?? '',
        'selloDirector' => $this->director?->sello?->ruta_storage ?? '',
        'anioAcademico' => 'Año Académico 2025 "José Dionisio de Herrera"',
        'codigoVerificacion' => $this->constancia?->hash ?? '',
        'correlativo' => $this->constancia->correlativo ?? 'DVUS-CONST-0001/2025',
        'pdf' => $this->pdf,
        'qrCode' => $this->qrPath,
    ];

    return array_merge($data, $this->getImagePaths($data));
}

    private function buildTitulo(): string
    {
        return match ($this->tipo) {
            'inscripcion' => 'CONSTANCIA DE REGISTRO DE PROYECTO DE VINCULACIÓN',
            'finalizacion' => 'CONSTANCIA DE FINALIZACIÓN DE ACTIVIDAD DE VINCULACIÓN',
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
    // se usa heredoc para evitar problemas con comillas
    return match ($this->tipo) {
        'inscripcion' => Blade::render(<<<BLADE
            <p >
                El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace CONSTAR que 
                <strong>{{ \$nombreCentro }}</strong> ha registrado el proyecto de vinculación denominado 
                <strong>{{ \$nombreProyecto }}</strong>, el cual se ejecuta en {{ \$municipios }}, Departamentos 
                {{ \$departamentos }} durante el período {{ \$fechaInicio }} hasta el 
                {{ \$fechaFinal }}, y fue registrado en esta dirección con el número 
                <strong>{{ \$codigoProyecto }}</strong>. El equipo docente responsable de la ejecución de este proyecto es el siguiente:
            </p>
            
        BLADE
        , compact(
            'nombreCentro',
            'nombreProyecto',
            'departamentos',
            'municipios',
            'fechaInicio',
            'fechaFinal',
            'codigoProyecto'
        )),

        'actualizacion' => Blade::render(<<<BLADE
            <p style="text-align: justify; line-height: 1.6; margin: 10px 0; font-size: 14px; font-family: 'Times New Roman', serif;">
                El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace CONSTAR que 
                <strong>UNAH Campus Atlántida</strong> ha registrado el proyecto de vinculación denominado 
                <strong>{{ \$nombreProyecto }}</strong>, el cual se ejecuta en los municipios de 
                <strong>{{ \$municipios }}</strong> durante el período 
                <strong>{{ \$fechaInicio }}</strong> hasta el <strong>{{ \$fechaFinal }}</strong>, 
                el cual fue registrado en esta dirección con el número 
                <strong>{{ \$codigoProyecto }}</strong>. A la fecha, el equipo docente responsable de la ejecución de este proyecto es el siguiente:
            </p>

           
        BLADE
        , compact(
            'nombreProyecto',
            'municipios',
            'fechaInicio',
            'fechaFinal',
            'codigoProyecto',
            'integrantes' 
        )),

        'finalizacion' => Blade::render(<<<BLADE
            <p>
                El Suscrito Director de Vinculación Universidad-Sociedad-VRA-UNAH, por este medio hace <strong>CONSTAR</strong> que el proyecto
                de vinculación denominado: <strong>{{ \$nombreProyecto }}</strong>, con código de registro <strong>{{ \$codigoProyecto }}</strong> 
                ha presentado el informe final que detalla los siguientes datos:
            </p>
        BLADE
        , [
            'codigoProyecto' => $codigoProyecto,
            'nombreProyecto' => $nombreProyecto,
        ]),

        default => '<p>N/D</p>',
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
