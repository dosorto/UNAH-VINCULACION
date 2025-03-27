<?php

namespace App\Livewire\Slide;

use App\Models\Slide\Slide;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Toggle;

class SlideConfig extends Component implements HasForms
{
    use InteractsWithForms;

    public array $slides = [];

    protected function getSlidesData(): array
    {
        return Slide::orderBy('id', 'desc')
            ->get()
            ->map(fn($slide) => [
                'id' => $slide->id,
                'image_url' => $slide->image_url,
                'estado' => $slide->estado,
            ])
            ->toArray();
    }

    protected function updateSlides(array $slides): void
    {
        // Obtener IDs enviados desde el formulario
        $submittedIds = collect($slides)->pluck('id')->filter()->all();

        // Obtener IDs existentes en la base de datos
        $existingIds = Slide::pluck('id')->all();

        // Determinar IDs que deben ser eliminados
        $deletedIds = array_diff($existingIds, $submittedIds);

        // Eliminar los registros no enviados
        if (!empty($deletedIds)) {
            Slide::whereIn('id', $deletedIds)->delete();
        }

        // Actualizar o crear los registros enviados
        foreach ($slides as $slide) {
            Slide::updateOrCreate(
                ['id' => $slide['id']],
                [
                    'image_url' => $slide['image_url'],
                    'estado' => $slide['estado'],
                ]
            );
        }
    }


    public function mount(): void
    {
        $this->slides = $this->getSlidesData();
        if (!empty($this->slides)) {
            $this->form->fill(['slides' => $this->slides]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('slides')
                    ->label('Slides')
                    ->schema([
                        Hidden::make('id')
                            ->default(0),
                        FileUpload::make('image_url')
                            ->label('Imagen')
                            ->disk('public')
                            ->directory('slides')
                            ->required(),
                        Toggle::make('estado')
                            ->inline(false)
                            ->label('Estado')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->defaultItems(3)
                    ->minItems(2)
                    ->grid(3)
                    ->deletable(true)
                    ->columns(1),
            ])
            ->statePath('slides');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $validatedData = collect($data['slides'])
            ->map(fn($slide) => [
                'id' => (int) $slide['id'] ?? null,
                'image_url' => $slide['image_url'],
                'estado' => (bool) $slide['estado'],
            ])
            ->toArray();

        $this->updateSlides($validatedData);

        Notification::make()
            ->title('Exito!')
            ->body('Modificado correctamente!')
            ->success()
            ->send();

        $this->mount();
    }

    public function render(): View
    {
        return view('livewire.slide.slide-config')
        ->layout('components.panel.modulos.modulo-configuracion');
    }
}
