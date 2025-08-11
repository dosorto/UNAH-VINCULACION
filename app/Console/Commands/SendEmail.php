<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Mail::raw('Este es un correo de prueba desde Laravel', function($message) {
            $message->to('dorian.ordonez@unah.edu.hn')
                    ->subject('Prueba SMTP Office 365');
        });

        $this->info('Correo de prueba enviado.');
    }
}
