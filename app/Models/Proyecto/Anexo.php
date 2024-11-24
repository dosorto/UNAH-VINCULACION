<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anexo extends Model
{
    //
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'proyecto_id',
        'documento_url',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    protected $table = 'anexo';
}
