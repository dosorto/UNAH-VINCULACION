<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Livewire\Attributes\On;

class Notifications extends Component
{
    public array $notifications = [];

    public function mount(): void
    {
        foreach (session()->pull('flash_notifications', []) as $n) {
            $this->notifications[] = $n;
        }
    }

    #[On('notify')]
    public function add(string $title, string $body = '', string $type = 'info', string $id = ''): void
    {
        $this->notifications[] = [
            'title' => $title,
            'body'  => $body,
            'type'  => $type,
            'id'    => $id ?: uniqid(),
        ];
    }

    public function remove(string $id): void
    {
        $this->notifications = array_values(
            array_filter($this->notifications, fn($n) => $n['id'] !== $id)
        );
    }

    public function render()
    {
        return view('livewire.components.notifications');
    }
}
