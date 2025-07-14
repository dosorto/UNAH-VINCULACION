<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IntegranteInternacionalProyecto extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'integrante_internacional_proyecto';

    protected $fillable = [
        'proyecto_id',
        'integrante_internacional_id',
        'rol',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'rol',
            ]);
    }

    // Relaciones
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function integranteInternacional(): BelongsTo
    {
        return $this->belongsTo(IntegranteInternacional::class);
    }
}
