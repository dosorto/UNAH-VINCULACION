<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;


class MicrosoftController extends Controller
{
    //

    public function redirectToMicrosoft()
     {
         return Socialite::driver('microsoft')->redirect();
     }

    // Redirigir al usuario a la página de inicio de sesión de Microsoft
    public function handleMicrosoftCallback()
    {
        try {
            $user = Socialite::driver('microsoft')->user();

            // Validar que el correo termine en '@unah.hn' o '@unah.edu.hn'
            $emailDomain = substr(strrchr($user->email, "@"), 1);
	    //dd(!in_array($emailDomain, ['unah.hn', 'unah.edu.hn']));
            if (!in_array($emailDomain, ['unah.hn', 'unah.edu.hn'])) {
                // Si el correo no pertenece a UNAH, simplemente redirige al login
                return redirect()->route('login')->withErrors('Solo se permiten correos institucionales UNAH.');
            }

            // Crear o buscar el usuario en la base de datos
            $user = User::firstOrCreate(
                ['email' => $user->email],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => bcrypt('123456dummy') // Cambié encrypt() por bcrypt()
                ]
            )->givePermissionTo('configuracion-admin-mi-perfil');

            Auth::login($user, true);
		//dd(auth()->user());
            // Crear o buscar el perfil de empleado
            $empleado = Empleado::firstOrCreate(
                ['user_id' => auth()->user()->id],
                ['user_id' => auth()->user()->id]
            );

            // Verificar si algún atributo de empleado es nulo
            if (
                is_null($empleado->nombre_completo) || is_null($empleado->numero_empleado) || is_null($empleado->celular) || is_null($empleado->categoria)
                || is_null($empleado->campus_id) || is_null($empleado->departamento_academico_id)
            ) {
                Notification::make()
                    ->title('Por favor, complete su perfil de empleado')
                    ->body('Por favor, complete su perfil de empleado para continuar.')
                    ->info()
                    ->send();
                return redirect()->route('mi_perfil');
            }

            return redirect()->route('inicio');
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
