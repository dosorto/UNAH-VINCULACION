<?php

namespace App\Livewire\Proyectos\Actualizacion;

use Livewire\Component;
use Filament\Forms\Form;
use App\Jobs\SendEmailJob;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\FichaActualizacion;
use Illuminate\Support\HtmlString;
use App\Models\Proyecto\CargoFirma;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Contracts\HasForms;

use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;


// Para enviar Emails
// use App\Mail\Correos\CorreoParticipacion;
use App\Livewire\Proyectos\Actualizacion\Secciones\PrimeraParte;
use App\Livewire\Proyectos\Actualizacion\Secciones\SegundaParte;


class EditProyectoActualizacion extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Proyecto $record;
    public Proyecto $proyecto;

    public function mount(Proyecto $proyecto)
    {
        $this->record = $proyecto;
        // validar que el coordinador del proyecto sea el usuario logueado
        if ($this->record->coordinador_proyecto[0]->empleado_id != auth()->user()->empleado->id) {
            Notification::make()
                ->title('¡Error!')
                ->body('No tienes permisos para editar este proyecto')
                ->danger()
                ->send();
            return redirect()->route('proyectosDocente');
        }


        if (in_array($this->record->obtenerUltimoEstado()
            ->tipo_estado_id, TipoEstado::whereIn('nombre', ['En Curso'])
            ->pluck('id')->toArray())) {
        // Cargar el proyecto con todas las relaciones necesarias
        $this->proyecto = Proyecto::with([
            'coordinador_proyecto',
            'empleado_proyecto',
            'estudiante_proyecto',
            'integrante_internacional_proyecto',
            'equipoEjecutorBajas',
            'metasContribuye',
            'carreras',
            'entidad_contraparte',
            'actividades.empleados',
            'presupuesto',
            'superavit',
            'odss',
            'anexos',
            'coordinador_proyecto.empleado'
        ])->find($this->record->id);            // Llenar el formulario manualmente con los datos del proyecto y campos adicionales
            $formData = $this->record->attributesToArray();
            
            // Agregar las relaciones al formulario
            $formData['estudiante_proyecto'] = $this->record->estudiante_proyecto->map(function ($estudiante) {
                return [
                    'id' => $estudiante->id,
                    'tipo_participacion_estudiante' => $estudiante->tipo_participacion_estudiante,
                    'asignatura_id' => $estudiante->asignatura_id,
                    'periodo_academico_id' => $estudiante->periodo_academico_id,
                    'cantidad_estudiantes_hombres' => $estudiante->cantidad_estudiantes_hombres,
                    'cantidad_estudiantes_mujeres' => $estudiante->cantidad_estudiantes_mujeres,
                    'total_estudiantes' => $estudiante->total_estudiantes,
                ];
            })->toArray();
            
            // Agregar fecha de finalización actual para mostrar en el formulario
            $formData['fecha_finalizacion_actual'] = $this->record->fecha_finalizacion ? 
                \Carbon\Carbon::parse($this->record->fecha_finalizacion)->format('d/m/Y') : 
                'No definida';
            
            /* Agregar campos de extensión de tiempo si existen fichas previas
            $ultimaFicha = \App\Models\Proyecto\FichaActualizacion::where('proyecto_id', $this->record->id)->latest()->first();
            if ($ultimaFicha) {
                $formData['fecha_ampliacion'] = $ultimaFicha->fecha_ampliacion;
                $formData['motivo_ampliacion'] = $ultimaFicha->motivo_ampliacion;
            }*/
            
            $this->form->fill($formData);
        } else {
            Notification::make()
                ->title('¡Error!')
                ->body('El proyecto no se encuentra en estado de "En Curso" para poder actualizar su equipo ejecutor.')
                ->danger()
                ->send();
            return redirect()->route('proyectosDocente');
        }

        // $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
       // $primeraParte = app(\App\Livewire\Proyectos\Actualizacion\Secciones\PrimeraParte::class, ['proyecto_id' => $this->record->id]);
        return $form
            ->model(null) // Evitar que Filament maneje automáticamente las relaciones
            ->schema([
                Wizard::make([
                    Wizard\Step::make('I.')
                        ->description('ACTUALIZACIÓN DEL EQUIPO EJECUTOR')
                        ->schema(
                           // $primeraParte->form(),
                           PrimeraParte::form(),
                        )
                        ->columns(2),

                    Wizard\Step::make('II.')
                        ->description('EXTENSIÓN DE TIEMPO DEL PROYECTO')
                        ->schema(
                            SegundaParte::form()
                        )
                        ->columns(2),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
            <x-filament::button
                type="submit"
                size="md"
                color="info"
            >
             Enviar a Firmar Actualización
            </x-filament::button>
        BLADE))),
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save()
    {
        $data = $this->form->getState();
        
        // Verificar si hay cambios
        $hayBajas = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $this->record->id)
            ->whereNull('ficha_actualizacion_id')
            ->where('estado_baja', 'pendiente') // Cambiado de false a 'pendiente'
            ->exists();

        $hayNuevos = !empty($data['empleado_proyecto']) || 
                    !empty($data['integrante_internacional_proyecto']);

        // Verificar si hubo cambios en las cantidades de estudiantes
        $hayCambiosCantidades = false;
        $cambiosCantidadesEstudiantes = []; // Guardar los cambios para la ficha
        
        if (!empty($data['estudiante_proyecto'])) {
            foreach ($data['estudiante_proyecto'] as $estudianteData) {
                // Si el registro tiene un ID, verificar si las cantidades cambiaron
                if (isset($estudianteData['id'])) {
                    $estudianteProyectoOriginal = \App\Models\Estudiante\EstudianteProyecto::find($estudianteData['id']);
                    if ($estudianteProyectoOriginal) {
                        if ($estudianteProyectoOriginal->cantidad_estudiantes_hombres != ($estudianteData['cantidad_estudiantes_hombres'] ?? 0) ||
                            $estudianteProyectoOriginal->cantidad_estudiantes_mujeres != ($estudianteData['cantidad_estudiantes_mujeres'] ?? 0)) {
                            $hayCambiosCantidades = true;
                            // NO actualizar inmediatamente, guardar para la ficha
                            $cambiosCantidadesEstudiantes[] = [
                                'estudiante_proyecto_id' => $estudianteData['id'],
                                'tipo_participacion' => $estudianteData['tipo_participacion_estudiante'] ?? null,
                                'asignatura_id' => $estudianteData['asignatura_id'] ?? null,
                                'periodo_academico_id' => $estudianteData['periodo_academico_id'] ?? null,
                                'cantidad_hombres_anterior' => $estudianteProyectoOriginal->cantidad_estudiantes_hombres,
                                'cantidad_mujeres_anterior' => $estudianteProyectoOriginal->cantidad_estudiantes_mujeres,
                                'cantidad_hombres_nueva' => $estudianteData['cantidad_estudiantes_hombres'] ?? 0,
                                'cantidad_mujeres_nueva' => $estudianteData['cantidad_estudiantes_mujeres'] ?? 0,
                            ];
                        }
                    }
                } else {
                    // Si no tiene ID, es un nuevo tipo de participación
                    $hayCambiosCantidades = true;
                    $cambiosCantidadesEstudiantes[] = [
                        'estudiante_proyecto_id' => null, // Es nuevo
                        'tipo_participacion' => $estudianteData['tipo_participacion_estudiante'] ?? null,
                        'asignatura_id' => $estudianteData['asignatura_id'] ?? null,
                        'periodo_academico_id' => $estudianteData['periodo_academico_id'] ?? null,
                        'cantidad_hombres_anterior' => 0,
                        'cantidad_mujeres_anterior' => 0,
                        'cantidad_hombres_nueva' => $estudianteData['cantidad_estudiantes_hombres'] ?? 0,
                        'cantidad_mujeres_nueva' => $estudianteData['cantidad_estudiantes_mujeres'] ?? 0,
                    ];
                }
            }
        }

        // Validar extensión de tiempo: ambos campos deben estar presentes
        $hayExtension = (!empty($data['fecha_ampliacion']) && !empty($data['motivo_ampliacion']));

        // Validar motivos de responsabilidades y razones de cambio
        $hayMotivos = !empty($data['motivo_responsabilidades_nuevos']) || !empty($data['motivo_razones_cambio']);

        // Verificar si hay AL MENOS UN cambio
        if (!$hayBajas && !$hayNuevos && !$hayExtension && !$hayMotivos && !$hayCambiosCantidades) {
            Notification::make()
                ->title('No se detectaron cambios')
                ->body('Debes realizar al menos un cambio para enviar la ficha de actualización a firmar:
                • Dar de baja a un integrante
                • Agregar nuevos integrantes  
                • Modificar cantidades de estudiantes
                • Solicitar extensión de tiempo (fecha y motivo)
                • Agregar motivos de responsabilidades o razones de cambio')
                ->danger()
                ->send();
            return;
        }

        // Validación adicional: si hay fecha de ampliación, debe haber motivo y viceversa
        if ((!empty($data['fecha_ampliacion']) && empty($data['motivo_ampliacion'])) ||
            (empty($data['fecha_ampliacion']) && !empty($data['motivo_ampliacion']))) {
            Notification::make()
                ->title('Campos incompletos')
                ->body('Para solicitar extensión de tiempo, debes llenar tanto la fecha como el motivo de ampliación.')
                ->danger()
                ->send();
            return;
        }

        // Excluir las relaciones del proceso de actualización automática
        // porque las manejamos manualmente con los botones de dar de baja/reincorporar
        $dataToUpdate = array_diff_key($data, [
            'coordinador_proyecto' => '',
            'empleado_proyecto' => '',
            'estudiante_proyecto' => '',
            'integrante_internacional_proyecto' => '',
            'equipo_ejecutor_bajas' => '',
            'motivo_responsabilidades_nuevos' => '',
            'motivo_razones_cambio' => '',
            'fecha_finalizacion_actual' => '', // Este campo no se actualiza en el proyecto, solo se guarda en la ficha
            'fecha_ampliacion' => '', // Esta fecha va a la ficha, no al proyecto
            'motivo_ampliacion' => '', // Este campo va a la ficha, no al proyecto
        ]);

        // Actualizar solo los campos del proyecto, no las relaciones
        $this->record->update($dataToUpdate);

        // Guardar snapshot del estado actual de estudiantes ANTES de cualquier cambio
        $estadoActualEstudiantes = $this->record->estudiante_proyecto->map(function ($ep) {
            return [
                'id' => $ep->id,
                'tipo_participacion' => $ep->tipo_participacion_estudiante,
                'asignatura_id' => $ep->asignatura_id,
                'periodo_academico_id' => $ep->periodo_academico_id,
                'cantidad_hombres' => $ep->cantidad_estudiantes_hombres,
                'cantidad_mujeres' => $ep->cantidad_estudiantes_mujeres,
                'total' => $ep->total_estudiantes,
            ];
        })->toArray();

        // Crear la ficha de actualización
        $fichaActualizacion = FichaActualizacion::create([
            'fecha_registro' => now(),
            'empleado_id' => auth()->user()->empleado->id,
            'proyecto_id' => $this->record->id,
            'integrantes_baja' => [], // Se llenará con los integrantes dados de baja
            'motivo_baja' => 'Actualización del equipo ejecutor',
            'fecha_baja' => now(),
            'fecha_ampliacion' => $data['fecha_ampliacion'] ?? null,
            'fecha_finalizacion_actual' => $this->record->fecha_finalizacion, // Guardar la fecha actual del proyecto
            'motivo_ampliacion' => $data['motivo_ampliacion'] ?? null,
            'motivo_responsabilidades_nuevos' => $data['motivo_responsabilidades_nuevos'] ?? null,
            'motivo_razones_cambio' => $data['motivo_razones_cambio'] ?? null,
            'cambios_cantidades_estudiantes' => $cambiosCantidadesEstudiantes, // Guardar cambios propuestos
            'estado_estudiantes_en_momento_ficha' => $estadoActualEstudiantes, // Guardar estado actual como snapshot
        ]);

        // Asociar las bajas pendientes con esta ficha de actualización
        $bajasPendientes = \App\Models\Proyecto\EquipoEjecutorBaja::where('proyecto_id', $this->record->id)
            ->where('estado_baja', 'pendiente')
            ->whereNull('ficha_actualizacion_id')
            ->get();
            
        foreach ($bajasPendientes as $baja) {
            $baja->update(['ficha_actualizacion_id' => $fichaActualizacion->id]);
        }

        // Asociar las solicitudes de nuevos integrantes pendientes con esta ficha de actualización
        $nuevosIntegrantesPendientes = \App\Models\Proyecto\EquipoEjecutorNuevo::where('proyecto_id', $this->record->id)
            ->where('estado_incorporacion', 'pendiente')
            ->whereNull('ficha_actualizacion_id')
            ->get();
            
        foreach ($nuevosIntegrantesPendientes as $nuevo) {
            $nuevo->update(['ficha_actualizacion_id' => $fichaActualizacion->id]);
        }

        // Crear TODAS las firmas necesarias para la ficha de actualización usando los cargos específicos
        $cargosFirmaActualizacion = CargoFirma::where('descripcion', 'Ficha_actualizacion')->get();
        
        foreach ($cargosFirmaActualizacion as $cargoFirma) {
            $estadoRevision = 'Pendiente';
            $firmaId = null;
            $selloId = null;
            $fechaFirma = null;
            $empleadoId = null;
            
            // Buscar el empleado correspondiente del proyecto original para este tipo de cargo
            $firmaOriginal = $this->record->firma_proyecto()
                ->whereHas('cargo_firma.tipoCargoFirma', function($query) use ($cargoFirma) {
                    $query->where('nombre', $cargoFirma->tipoCargoFirma->nombre);
                })
                ->where('firmable_type', Proyecto::class)
                ->first();
            
            if ($firmaOriginal) {
                $empleadoId = $firmaOriginal->empleado_id;
                
                // Si es el coordinador del proyecto (quien envía), auto-aprobar su firma
                if ($cargoFirma->tipoCargoFirma->nombre === 'Coordinador Proyecto') {
                    $estadoRevision = 'Aprobado';
                    $firmaId = auth()->user()?->empleado?->firma?->id;
                    $selloId = auth()->user()?->empleado?->sello?->id;
                    $fechaFirma = now();
                }
                
                $fichaActualizacion->firma_proyecto()->create([
                    'empleado_id' => $empleadoId,
                    'cargo_firma_id' => $cargoFirma->id,
                    'estado_revision' => $estadoRevision,
                    'firma_id' => $firmaId,
                    'sello_id' => $selloId,
                    'hash' => 'hash',
                    'fecha_firma' => $fechaFirma,
                ]);
            }
        }

        // Crear el estado inicial para la FICHA DE ACTUALIZACIÓN
        $primerCargoFirma = CargoFirma::where('descripcion', 'Ficha_actualizacion')
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
            ->first();

        $fichaActualizacion->estado_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => $primerCargoFirma->estado_siguiente_id,
            'fecha' => now(),
            'comentario' => 'Ficha de actualización del proyecto enviada a firmar',
        ]);

        // Enviar correo de notificación
        try {
            \Illuminate\Support\Facades\Mail::to(auth()->user()->email)->send(new \App\Mail\FichaActualizacionCreada($fichaActualizacion, auth()->user()));
        } catch (\Exception $emailException) {
            // Log del error pero no fallar el proceso
            Log::warning('Error al enviar correo de ficha de actualización enviada: ' . $emailException->getMessage());
        }

        Notification::make()
            ->title('¡Éxito!')
            ->body('La ficha de actualización del proyecto ha sido enviada a firmar exitosamente')
            ->success()
            ->send();
        return redirect()->route('FichasActualizacionDocente');
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $record = null;
        
        try {
            // Intentar obtener el estado del formulario y crear el proyecto
            $data['fecha_registro'] = now();
            $record = Proyecto::create($data);
            $this->form->model($record)->saveRelationships();
        } catch (\Exception $e) {
            // Notificación de error si ocurre al crear el proyecto
            // Solo eliminar el registro si fue creado exitosamente
            if ($record) {
                $record->delete();
            }

            Notification::make()
                ->title('Error')
                ->body('Error al crear el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

        try {
            // Intentar agregar o actualizar la firma
            $firmaP = $record->firma_proyecto()->updateOrCreate(
                [
                    'empleado_id' => auth()->user()->empleado->id,
                    'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                        ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                        ->where('cargo_firma.descripcion', 'Proyecto')
                        ->first()->id,
                ],
                [
                    'estado_revision' => 'Aprobado',
                    'firma_id' => auth()->user()?->empleado?->firma?->id,
                    'sello_id' => auth()->user()?->empleado?->sello?->id,
                    'hash' => 'hash',
                    'fecha_firma' => now(),
                ]
            );
            
            // Intentar agregar el estado del proyecto
            // Intentar agregar el estado del proyecto
            $record->estado_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'tipo_estado_id' => $firmaP->cargo_firma->estado_siguiente_id,
                'fecha' => now(),
                'comentario' => 'Proyecto de actualización creado exitosamente y enviado a firmar',
            ]);
            
            // Enviar correo de notificación
            try {
                \Illuminate\Support\Facades\Mail::to(auth()->user()->email)->send(new \App\Mail\ProyectoCreado($record, auth()->user()));
            } catch (\Exception $emailException) {
                // Log del error pero no fallar la creación del proyecto
                \Log::warning('Error al enviar correo de proyecto de actualización creado: ' . $emailException->getMessage());
            }
            
        } catch (\Exception $e) {
            // Eliminar el proyecto en caso de error
            $record->delete();
            Notification::make()
                ->title('Error')
                ->body('Error al procesar el proyecto: ' . $e->getMessage())
                ->danger()
                ->send();
            return;
        }

            
        // Notificación de éxito si todo se completó correctamente
        Notification::make()
            ->title('¡Éxito!')
            ->body('Proyecto creado correctamente')
            ->success()
            ->send();

        $this->js('location.reload();');
    }

    public function borrador(): void
    {
        $data = $this->form->getState();

        // Excluir las relaciones del proceso de actualización automática
        $dataToUpdate = array_diff_key($data, [
            'coordinador_proyecto' => '',
            'empleado_proyecto' => '',
            'estudiante_proyecto' => '',
            'integrante_internacional_proyecto' => '',
            'equipo_ejecutor_bajas' => ''
        ]);

        // dd($data);
        $this->record->update($dataToUpdate);
        $this->record->save();
        // dd($this->record->presupuesto);
        Notification::make()
            ->title('¡Éxito!')
            ->body('Cambios guardados')
            ->success()
            ->send();
        $this->js('location.reload();');
    }

    public function render(): View
    {
        return view('livewire.proyectos.actualizacion.create-proyecto-actualizacion')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}
