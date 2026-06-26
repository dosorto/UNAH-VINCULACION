<?php

namespace Database\Seeders\Personal;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class NotificacionesPoaRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roleNames = [
            'Coordinador Proyecto',
            'Enlace Vinculacion',
            'Jefe Departamento',
            'Director centro',
            'Revisor Vinculacion',
            'Director Vinculacion',
            'SGCU Gestor',
            'SGCU Revisor Etapa 1',
            'SGCU Revisor Etapa 2',
        ];

        $user = User::where('email', 'notificacionespoa@unah.edu.hn')->first();

        if (! $user) {
            $this->command?->warn('No se encontro el usuario notificacionespoa@unah.edu.hn.');
            return;
        }

        $roles = collect($roleNames)
            ->map(fn (string $name) => Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $user->roles()->syncWithoutDetaching($roles->pluck('id')->all());
    }
}
