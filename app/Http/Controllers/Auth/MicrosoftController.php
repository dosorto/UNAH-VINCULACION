<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Filament\Notifications\Notification;

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
    
            return redirect()->route('crearPais');
            
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
