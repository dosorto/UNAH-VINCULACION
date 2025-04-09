<?php

namespace App\Models\Constancia;

use Illuminate\Database\Eloquent\Model;

class DescargaConstancia extends Model
{
    public $timestamps = false;

    protected $fillable = ['constancia_id', 'user_id', 'descargado_en'];

    public function constancia()
    {
        return $this->belongsTo(Constancia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}