<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anexo extends Model
{
    //
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'proyecto_id',
        'tipo_anexo_id',
        'documento_url',
        'nombre_archivo',
        'detalle',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function tipoAnexo(): BelongsTo
    {
        return $this->belongsTo(TipoAnexo::class, 'tipo_anexo_id');
    }

    protected $table = 'anexo';
}
