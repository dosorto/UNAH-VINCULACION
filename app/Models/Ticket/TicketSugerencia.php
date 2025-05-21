<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSugerencia extends Model
{
    use HasFactory;

    protected $table = 'ticket_sugerencia';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'mensaje',
        'estado',
    ];
    
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }


    protected $dates = ['fecha_envio'];

    public function sugerencia()
    {
        return $this->belongsTo(Sugerencia::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
