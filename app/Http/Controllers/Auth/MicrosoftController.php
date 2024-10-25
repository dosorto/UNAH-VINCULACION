<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class MicrosoftController extends Controller
{
    //
     // Redirigir al usuario a la página de inicio de sesión de Microsoft
     public function redirectToMicrosoft()
     {
         return Socialite::driver('microsoft')->redirect();
     }
 
     // Manejar el callback de Microsoft
     public function handleMicrosoftCallback()
     {
        
        try {
            $user = Socialite::driver('microsoft')->user();
    
            // Crear o buscar el usuario en la base de datos
            $user = User::firstOrCreate(
                ['email' => $user->email],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => bcrypt('123456dummy') // Cambié encrypt() por bcrypt()
                ]
            );
    
            Auth::login($user, true);
            $empleado = Empleado::firstOrCreate(
                ['user_id' => auth()->user()->id],
                ['user_id' => auth()->user()->id]
            );
            // verificar si algun atributo de empleado es nulo
            if (is_null($empleado->nombre_completo) || is_null($empleado->numero_empleado) || is_null($empleado->celular) || is_null($empleado->categoria)
                || is_null($empleado->campus_id) || is_null($empleado->departamento_academico_id)) {
                Notification::make()
                    ->title('Por favor, complete su perfil de empleado')
                    ->body('Por favor, complete su perfil de empleado para continuar.')
                    ->info()
                    ->send();
                return redirect()->route('mi_perfil');
            }

            return redirect()->route('listarProyectosVinculacion');
            
        } catch (\Exception $e) {
            Notification::make()
            ->title('Algo salió mal al iniciar sesión con Microsoft! :(')
            ->body($e->getMessage())
            ->danger()
            ->send();
            
            // Redirigir a la ruta de login
            return redirect()->route('login');
        }
     }
}
