<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Filament\Notifications\Notification;

class SetRoleController extends Controller
{
    //
    public function SetRole(Request $request, $role_id)
    {

        $request->merge(['role_id' => $role_id]); // Añade el parámetro al request para validarlo

        $request->validate([
            'role_id' => ['required', 'exists:roles,id']
        ]);



        $user = Auth::user();
        $roleToChange = Role::findOrFail($request->role_id);

        // Verificar si el usuario tiene el rol antes de cambiarlo
        if (!$user->hasRole($roleToChange->name)) {
            Notification::make()
                ->title('Error')
                ->body('No tienes permiso para cambiar a este rol. :(')
                ->danger()
                ->send();
            return redirect()->route('inicio')->with('error', 'No tienes permiso para cambiar a este rol.');
        }


        // Asignar el nuevo rol si pasa la validación
        $user->active_role_id = $request->role_id;
        $user->save();

        Notification::make()
        ->title('Exito!')
        ->body('Rol cambiado exitosamente.')
        ->success()
        ->send();

        // Redirigir a la ruta 'inicio' con un mensaje de éxito
        return redirect()->route('inicio')->with('success', 'Rol cambiado exitosamente.');
    }
}
