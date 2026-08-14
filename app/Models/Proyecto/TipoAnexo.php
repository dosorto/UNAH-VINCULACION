<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoAnexo extends Model
{
    use SoftDeletes;

    public const CODIGO_CARTA_SOLICITUD = 'carta_solicitud_contraparte';

    public const CODIGO_CONVENIO_CARTA = 'convenio_carta_intenciones';

    public const CODIGO_OFICIO_REMISION = 'oficio_remision_decano_director';

    public const CODIGO_OTROS = 'otros';

    public const TIPOS_BASE = [
        [
            'codigo' => self::CODIGO_CARTA_SOLICITUD,
            'nombre' => 'Carta de solicitud del proyecto firmada por el representante legal de la contraparte',
            'requiere_detalle' => false,
            'orden' => 1,
        ],
        [
            'codigo' => self::CODIGO_CONVENIO_CARTA,
            'nombre' => 'Convenio/carta de intenciones firmada entre la UNAH y contraparte',
            'requiere_detalle' => false,
            'orden' => 2,
        ],
        [
            'codigo' => self::CODIGO_OFICIO_REMISION,
            'nombre' => 'Oficio de remisión del Decano/Director Centro Regional',
            'requiere_detalle' => false,
            'orden' => 3,
        ],
        [
            'codigo' => self::CODIGO_OTROS,
            'nombre' => 'Otros (detallar)',
            'requiere_detalle' => true,
            'orden' => 4,
        ],
    ];

    protected $table = 'tipos_anexo';

    protected $fillable = [
        'codigo',
        'nombre',
        'requiere_detalle',
        'orden',
        'activo',
    ];

    protected $casts = [
        'requiere_detalle' => 'boolean',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class, 'tipo_anexo_id');
    }
}
