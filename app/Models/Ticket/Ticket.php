<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'ticket';

    protected $fillable = [
        'user_id',
        'tipo_ticket',
        'asunto',
        'estado',
    ];

    protected $dates = ['fecha_creacion', 'fecha_cierre'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tickets()
    {
        return $this->hasMany(SugerenciaTicket::class);
    }

    public function mensajes()
    {
         return $this->hasMany(TicketSugerencia::class, 'ticket_id');
    }

    
}
