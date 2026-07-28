<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EntidadContraparteProyecto extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'entidad_contraparte_proyecto';

    protected $fillable = [
        'proyecto_id',
        'entidad_contraparte_id',
        'nombre',
        'tipo_entidad',
        'nombre_contacto',
        'cargo_contacto',
        'telefono',
        'correo',
        'rtn',
        'descripcion_acuerdos',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rtn', 'descripcion_acuerdos']);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function entidadContraparte(): BelongsTo
    {
        return $this->belongsTo(EntidadContraparte::class);
    }

    public function instrumentoFormalizacion()
    {
        return $this->hasMany(InstrumenFormalizacion::class, 'entidad_contraparte_id');
    }
}
