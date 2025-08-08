<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstrumenFormalizacion extends Model
{
    //
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'entidad_contraparte_id',
        'tipo_documento',
        'documento_url',
    ];

    /**
     * Get the display text for the tipo_documento field
     */
    public function getTipoDocumentoDisplayAttribute()
    {
        $tipos = [
            'carta_formal_solicitud' => 'Carta formal de solicitud a la unidad académica',
            'carta_intenciones' => 'Carta de intenciones con la UNAH',
            'convenio_marco' => 'Convenio marco con la UNAH',
        ];

        return $tipos[$this->tipo_documento] ?? $this->tipo_documento;
    }

    public function entidadContraparte()
    {
        return $this->belongsTo(EntidadContraparte::class, 'entidad_contraparte_id');
    }

    protected $table = 'instrumento_formalizacion';
}
