<?php

namespace App\Support;

use Livewire\Livewire;

class Notification
{
    private string $title = '';
    private string $body = '';
    private string $type = 'info';

    public static function make(): static
    {
        return new static();
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function success(): static
    {
        $this->type = 'success';
        return $this;
    }

    public function danger(): static
    {
        $this->type = 'danger';
        return $this;
    }

    public function warning(): static
    {
        $this->type = 'warning';
        return $this;
    }

    public function info(): static
    {
        $this->type = 'info';
        return $this;
    }

    public function send(): void
    {
        $data = [
            'title' => $this->title,
            'body'  => $this->body,
            'type'  => $this->type,
            'id'    => uniqid(),
        ];

        // Para redirecciones (controladores HTTP): guardar en sesión
        session()->push('flash_notifications', $data);

        // Para requests Livewire (AJAX): despachar evento al componente
        if (Livewire::isLivewireRequest()) {
            $component = Livewire::current();
            if ($component && method_exists($component, 'dispatch')) {
                $component->dispatch(
                    'notify',
                    title: $data['title'],
                    body: $data['body'],
                    type: $data['type'],
                    id: $data['id']
                );
            }
        }
    }
}
