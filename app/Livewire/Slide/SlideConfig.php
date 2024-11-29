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

    public function mount(): void
    {
        // Precargar todos los registros de la tabla Slide
        $this->slides = [
            'slides' => Slide::orderBy('id', 'desc') // Ordena por el id de mayor a menor
                ->get() // Obtiene los registros
                ->map(fn ($slide) => [
                    'id' => $slide->id,
                    'image_url' => $slide->image_url,
                    'estado' => $slide->estado,
                ])
                ->toArray()
        ];
        // dd($this->slides);
        $this->form->fill($this->slides);
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
                    ->grid(3)
                    ->deletable(false)
                    ->columns(1),
            ])
            ->statePath('slides');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // dd($data);
        foreach ($data['slides'] as $slide) {
            Slide::firstOrCreate(
                [
                    'id' => $slide['id']
                ],
                [
                    'image_url' => $slide['image_url'],
                    'estado' => $slide['estado']
                ]
            )
                ->update([
                    'image_url' => $slide['image_url'],
                    'estado' => $slide['estado'],
                ]);
        }
        Notification::make()
            ->title('Exito!')
            ->body('Modificado correctamente!')
            ->success()
            ->send();

        $this->js('location.reload();');
    }

    public function render(): View
    {
        return view('livewire.slide.slide-config');
    }
}
