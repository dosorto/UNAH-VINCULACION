<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

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
         $user = Socialite::driver('microsoft')->user();

        // 

 
         // Lógica para manejar el usuario autenticado
         // Por ejemplo, guardar el usuario en la base de datos o iniciar sesión
         if ($user->tenant === null) {
             // Manejar cuentas de consumidor
         } else {
             // Manejar cuentas laborales o escolares
         }
         
         // Redirigir o iniciar sesión según sea necesario
     }
}
