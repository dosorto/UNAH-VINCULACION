<?php

namespace App\Models\Constancia;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use App\Traits\EstadoTrait;


class Constancia extends Model
{
    use EstadoTrait;

    protected $table = 'constancia';

    protected $fillable = [
        'origen_type', 'origen_id',
        'destinatario_type', 'destinatario_id',
        'tipo_constancia_id', 'status', 'validaciones',
        'hash'
    ];


    protected static function booted()
    {
        static::creating(function ($constancia) {
            // Generar hash entre 15 y 25 caracteres
            $length = rand(15, 25);

            do {
                $hash = Str::random($length);
            } while (self::where('hash', $hash)->exists());

            $constancia->hash = $hash;
        });
    }


    // Relación polimórfica al modelo de origen
    public function origen()
    {
        return $this->morphTo();
    }

    // Relación polimórfica al modelo destinatario
    public function destinatario()
    {
        return $this->morphTo(__FUNCTION__, 'destinatario_type', 'destinatario_id');
    }

    // Relación con el catálogo de tipos de constancia
    public function tipo()
    {
        return $this->belongsTo(TipoConstancia::class, 'tipo_constancia_id');
    }

    // Relación con descargas
    public function descargas()
    {
        return $this->hasMany(DescargaConstancia::class);
    }


}
