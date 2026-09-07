<?php
namespace App\Mail;
use App\Models\Pasantia;
use Illuminate\Bus\Queueable; use Illuminate\Mail\Mailable; use Illuminate\Queue\SerializesModels;
class PasantiaFlujoNotificacion extends Mailable { use Queueable, SerializesModels; public function __construct(public Pasantia $registro, public string $evento) {} public function build(){ return $this->subject('Actualización de Pasantía '.$this->registro->codigo_registro)->view('emails.pasantia-flujo'); } }
