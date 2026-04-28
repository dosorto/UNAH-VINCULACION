<?php

namespace App\Livewire\Slide;

use App\Models\Slide\Slide;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class SlideConfig extends Component
{
    use WithFileUploads;

    public $newImage = null;
    public bool $newEstado = true;

    protected array $rules = [
        'newImage' => 'required|image|max:5120',
    ];

    public function addSlide(): void
    {
        $this->validate();

        $path = $this->newImage->store('slides', 'public');

        Slide::create([
            'image_url' => $path,
            'estado'    => $this->newEstado,
        ]);

        $this->newImage = null;
        $this->newEstado = true;
        Notification::make()->title('Slide agregado')->body('El slide fue agregado correctamente.')->success()->send();
    }

    public function toggleEstado(int $id): void
    {
        $slide = Slide::findOrFail($id);
        $slide->update(['estado' => !$slide->estado]);
    }

    public function deleteSlide(int $id): void
    {
        Slide::findOrFail($id)->delete();
        Notification::make()->title('Slide eliminado')->body('El slide fue eliminado correctamente.')->success()->send();
    }

    public function render(): View
    {
        $slides = Slide::orderBy('id', 'desc')->get();

        return view('livewire.slide.slide-config', compact('slides'));
    }
}