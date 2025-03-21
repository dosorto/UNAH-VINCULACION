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

            // validar que sea una cuenta de la UNAH primero
            if (!in_array(substr(strrchr($user->email, "@"), 1), ['unah.hn', 'unah.edu.hn'])) {
                Notification::make()
                    ->title('Correo no válido')
                    ->body('Inicie sesion con un correo de la UNAH.')
                    ->danger()
                    ->send();
                return redirect()->route('login');
            }

            // buscar el user en la base de datos  por medio del id de microsoft
            $testUser = User::where('microsoft_id', $user->id)->first();

            // Si el usuario no existe, crearlo con los datos de Microsoft y ademas es un empleado 
            if (!$testUser) {

                $numero = $user->employeeId;


                $user = User::create([
                    'name' => $user->displayName,
                    'email' => $user->email,
                    'microsoft_id' => $user->id,
                    'given_name' => $user->givenName,
                    'surname' => $user->surname,
                ]);

                // su correo electronico termina en '@unah.edu.hn'
                if (substr(strrchr($user->email, "@"), 1) == 'unah.edu.hn') {
                    // crear un perfil de empleado
                    $user->empleado()->create([
                        'nombre_completo' => $user->name,
                        'numero_empleado' => $numero,
                    ]);

                    $user->givePermissionTo('configuracion-admin-mi-perfil')
                        ->givePermissionTo('docente-cambiar-datos-personales');
    
                } else if (substr(strrchr($user->email, "@"), 1) == 'unah.hn') {
                    // crear un perfil de estudiante
                    $user->estudiante()->create([
                        'nombre_completo' => $user->name,
                        'numero_cuenta' => $numero,
                    ]);
    
                }
            } else {
                $user = $testUser;
            }
            Auth::login($user, true);
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
