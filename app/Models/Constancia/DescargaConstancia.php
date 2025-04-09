<?php

namespace App\Models\Constancia;

use Illuminate\Database\Eloquent\Model;

class DescargaConstancia extends Model
{
    public $timestamps = false;
    protected $table = 'descarga_constancia';

    protected $fillable = ['constancia_id', 'user_id', 'descargado_en'];




    // establecer la fecha de creacion cada ves que se descargue

    protected static function booted()
    {
        static::creating(function ($descarga) {
            $descarga->descargado_en = now();
        });
    }



    public function constancia()
    {
        return $this->belongsTo(Constancia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}