<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoCreado;
use App\Models\Proyecto\Proyecto;
use App\Models\User;

class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {--proyecto=} {--usuario=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar envío de correo de proyecto creado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Envío de correo básico
      /*  Mail::raw('Este es un correo de prueba desde Laravel', function($message) {
            $message->to('dorian.ordonez@unah.edu.hn')
                    ->subject('Prueba SMTP Office 365');
        });*/

        // Probar correo de proyecto creado si se proporcionan parámetros
        $proyectoId = $this->option('proyecto');
        $usuarioId = $this->option('usuario');

        if ($proyectoId && $usuarioId) {
            $proyecto = Proyecto::find($proyectoId);
            $usuario = User::find($usuarioId);

            $usuario->email = 'acxel.aplicano@unah.hn';
            if ($proyecto && $usuario) {
                try {
                    /*Mail::raw(new ProyectoCreado($proyecto, $usuario), function($message) {
                        $message->to($usuario->email)->subject('Prueba SMTP Office 365');
                    });*/
                    //$message->to($usuario->email)->subject('Proyecto Creado - ' . $proyecto->nombre_proyecto)->send();
                     Mail::to($usuario->email)->send(new ProyectoCreado($proyecto, $usuario));
                    $this->info("Correo de proyecto creado enviado a {$usuario->email}");
                } catch (\Exception $e) {
                    $this->error("Error al enviar correo: " . $e->getMessage());
                }
            } else {
                $this->error('Proyecto o usuario no encontrado');
            }
        }
    }
}
