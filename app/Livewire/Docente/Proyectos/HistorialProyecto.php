<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Estado\TipoEstado;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Support\Enums\MaxWidth;

class HistorialProyecto extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public $proyecto;
    public $logs = [];
    public $estados = [];
    public $esCoordinador = false;

    public function mount(Proyecto $proyecto)
{
    $this->proyecto = $proyecto;
    
    // Verificar si el usuario actual es el coordinador del proyecto
    $user = auth()->user();
    if ($user && $user->empleado) {
        $this->esCoordinador = $proyecto->coordinador && $proyecto->coordinador->id === $user->empleado->id;
    }
    
    // Buscar EmpleadoProyecto, pero no fallar si no existe
    $empleadoProyecto = EmpleadoProyecto::where('proyecto_id', $proyecto->id)->first();
    
    // Solo autorizar si existe el registro
    if ($empleadoProyecto) {
        $this->authorize('view', $empleadoProyecto);
    } else {
        // Si no existe, verificar que el usuario sea admin, coordinador o firmante del proyecto
        $user = auth()->user();
        
        if (!$user || !$user->empleado) {
            abort(403, 'No tiene permiso para ver este proyecto');
        }
        
        $esCoordinador = $proyecto->coordinador && $proyecto->coordinador->id === $user->empleado->id;
        $esAdmin = $user->hasAnyRole(['admin', 'Director/Enlace', 'Revisor Vinculacion']);
        
        // Verificar si el usuario es firmante del proyecto
        $esFirmante = \App\Models\Proyecto\FirmaProyecto::where('firmable_type', Proyecto::class)
            ->where('firmable_id', $proyecto->id)
            ->where('empleado_id', $user->empleado->id)
            ->exists();
        
        if (!$esCoordinador && !$esAdmin && !$esFirmante) {
            abort(403, 'No tiene permiso para ver este proyecto. Solo el coordinador, firmantes del proyecto o un administrador pueden acceder al historial.');
        }
    }

    // Obtener IDs de los documentos asociados al proyecto
    $documentosIds = DocumentoProyecto::where('proyecto_id', $proyecto->id)->pluck('id')->toArray();

    // Obtener todos los estados asociados al proyecto y a sus documentos (historial de movimientos)
    $this->estados = EstadoProyecto::where(function ($query) use ($proyecto, $documentosIds) {
            $query->where(function ($q) use ($proyecto) {
                $q->where('estadoable_type', Proyecto::class)
                  ->where('estadoable_id', $proyecto->id);
            });
            if (!empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)
                      ->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
        ->orderByDesc('created_at')
        ->get();
}

    public function subirInformeIntermedioAction(): Action
    {
        return Action::make('subirInformeIntermedio')
            ->label(fn() => $this->proyecto->documento_intermedio()?->estado?->tipoestado?->nombre == 'Subsanacion'
                ? 'Subsanar Informe Intermedio'
                : 'Subir Informe Intermedio')
            ->icon('heroicon-o-document-arrow-up')
            ->color('warning')
            ->modalHeading('Documentos del Proyecto')
            ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
            ->modalWidth(MaxWidth::SevenExtraLarge)
            ->form([
                Repeater::make('documentos')
                    ->schema([
                        Hidden::make('tipo_documento')
                            ->default('Informe Intermedio'),
                        TextInput::make('dd')
                            ->label('Tipo de Informe')
                            ->disabled()
                            ->default('Informe Intermedio'),
                        FileUpload::make('documento_url')
                            ->label('Informe Intermedio')
                            ->required()
                            ->acceptedFileTypes(['application/pdf']),
                    ])
                    ->addable(false)
                    ->reorderable(false)
                    ->deletable(false),
            ])
            ->action(function (array $data) {
                $proyecto = $this->proyecto;

                $proyecto->documentos()
                    ->where('tipo_documento', 'Informe Intermedio')
                    ->each(function ($documento) {
                        $documento->firma_documento()->delete();
                        $documento->estado_documento()->delete();
                    });

                $proyecto->documentos()
                    ->where('tipo_documento', 'Informe Intermedio')
                    ->delete();

                $documentoIntermedio = $proyecto->documentos()->create([
                    'tipo_documento' => $data['documentos'][0]['tipo_documento'],
                    'documento_url'  => $data['documentos'][0]['documento_url'],
                ]);

                $cargosFirmas = CargoFirma::where('descripcion', 'Documento_intermedio')->get();
                $cargosFirmas->each(function ($cargo) use ($proyecto, $documentoIntermedio) {
                    $documentoIntermedio->firma_documento()->create([
                        'empleado_id'    => $proyecto->getFirmabyCargo($cargo->tipoCargoFirma->nombre)->empleado->id,
                        'cargo_firma_id' => $cargo->id,
                        'estado_revision' => 'Pendiente',
                        'hash'           => 'hash',
                    ]);
                });

                $documentoIntermedio->estado_documento()->create([
                    'empleado_id'   => auth()->user()->empleado->id,
                    'tipo_estado_id' => TipoEstado::where('nombre', 'Enlace Vinculacion')->first()->id,
                    'fecha'         => now(),
                    'comentario'    => 'Documento creado',
                ]);

                Notification::make()
                    ->title('Éxito')
                    ->body('Informe Intermedio subido correctamente')
                    ->success()
                    ->send();
            });
    }

    public function subirInformeFinalAction(): Action
    {
        return Action::make('subirInformeFinal')
            ->label(fn() => $this->proyecto->documento_final()?->estado?->tipoestado?->nombre == 'Subsanacion'
                ? 'Subsanar Informe Final'
                : 'Subir Informe Final')
            ->icon('heroicon-o-document-arrow-up')
            ->color('info')
            ->modalHeading('Documentos del Proyecto')
            ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
            ->modalWidth(MaxWidth::SevenExtraLarge)
            ->form([
                Repeater::make('documentos')
                    ->schema([
                        Hidden::make('tipo_documento')
                            ->default('Informe Final'),
                        TextInput::make('informe_final')
                            ->label('Tipo de Informe')
                            ->disabled()
                            ->default('Informe Final'),
                        FileUpload::make('documento_url')
                            ->label('Informe Final')
                            ->required()
                            ->acceptedFileTypes(['application/pdf']),
                    ])
                    ->addable(false)
                    ->reorderable(false)
                    ->deletable(false),
            ])
            ->action(function (array $data) {
                $proyecto = $this->proyecto;

                $proyecto->documentos()
                    ->where('tipo_documento', 'Informe Final')
                    ->each(function ($documento) {
                        $documento->firma_documento()->delete();
                        $documento->estado_documento()->delete();
                    });

                $proyecto->documentos()
                    ->where('tipo_documento', 'Informe Final')
                    ->delete();

                $documentoFinal = $proyecto->documentos()->create([
                    'tipo_documento' => $data['documentos'][0]['tipo_documento'],
                    'documento_url'  => $data['documentos'][0]['documento_url'],
                ]);

                $cargosFirmas = CargoFirma::where('descripcion', 'Documento_final')->get();
                $cargosFirmas->each(function ($cargo) use ($proyecto, $documentoFinal) {
                    $documentoFinal->firma_documento()->create([
                        'empleado_id'    => $proyecto->getFirmabyCargo($cargo->tipoCargoFirma->nombre)->empleado->id,
                        'cargo_firma_id' => $cargo->id,
                        'estado_revision' => 'Pendiente',
                        'hash'           => 'hash',
                    ]);
                });

                $documentoFinal->estado_documento()->create([
                    'empleado_id'    => auth()->user()->empleado->id,
                    'tipo_estado_id' => TipoEstado::where('nombre', 'Enlace Vinculacion')->first()->id,
                    'fecha'          => now(),
                    'comentario'     => 'Documento creado',
                ]);

                Notification::make()
                    ->title('Éxito')
                    ->body('Informe Final subido correctamente')
                    ->success()
                    ->send();
            });
    }

    public function render()
    {
        return view('livewire.docente.proyectos.historial-proyecto', [
            'proyecto' => $this->proyecto,
            'estados' => $this->estados
        ]);
    }
}