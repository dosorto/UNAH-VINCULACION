<?php

namespace App\Models\Presupuesto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Presupuesto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = [
        'id',
        'proyecto_id',
        'aporte_internacionales',
        'aporte_otras_universidades',
        'aporte_contraparte',
        'aporte_comunidad',
        'otros_aportes',
    ];

    protected static $logName = 'Presupuesto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'tipo_presupuesto_id', 'administrador_id', 'proyecto_id'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'id',
        'proyecto_id',
        'aporte_estudiantes',
        'aporte_profesores',
        'aporte_internacionales',
        'aporte_otras_universidades',
        'aporte_academico_unah',
        'aporte_transporte_unah',
        'aporte_contraparte',
        'aporte_comunidad',
        'otros_aportes',
        // Campos detallados del presupuesto
        'cantidad_horas_docentes',
        'costo_unitario_docentes', 
        'costo_total_docentes',
        'cantidad_horas_estudiantes',
        'costo_unitario_estudiantes',
        'costo_total_estudiantes',
        'cantidad_movilizacion',
        'costo_unitario_movilizacion',
        'costo_total_movilizacion',
        'cantidad_utiles',
        'costo_unitario_utiles',
        'costo_total_utiles',
        'cantidad_impresion',
        'costo_unitario_impresion',
        'costo_total_impresion',
        'cantidad_infraestructura',
        'costo_unitario_infraestructura',
        'costo_total_infraestructura',
        'cantidad_servicios',
        'costo_unitario_servicios',
        'costo_total_servicios'
    ];

    protected $attributes = [
        // Valores por defecto para campos detallados
        'cantidad_horas_docentes' => 0,
        'costo_unitario_docentes' => 0,
        'costo_total_docentes' => 0,
        'cantidad_horas_estudiantes' => 0,
        'costo_unitario_estudiantes' => 0,
        'costo_total_estudiantes' => 0,
        'cantidad_movilizacion' => 0,
        'costo_unitario_movilizacion' => 0,
        'costo_total_movilizacion' => 0,
        'cantidad_utiles' => 0,
        'costo_unitario_utiles' => 0,
        'costo_total_utiles' => 0,
        'cantidad_impresion' => 0,
        'costo_unitario_impresion' => 0,
        'costo_total_impresion' => 0,
        'cantidad_infraestructura' => 0,
        'costo_unitario_infraestructura' => 0,
        'costo_total_infraestructura' => 0,
        'cantidad_servicios' => 0,
        'costo_unitario_servicios' => 0,
        'costo_total_servicios' => 0
    ];

    protected $casts = [
        // Casts para campos detallados
        'cantidad_horas_docentes' => 'decimal:2',
        'costo_unitario_docentes' => 'decimal:2',
        'costo_total_docentes' => 'decimal:2',
        'cantidad_horas_estudiantes' => 'decimal:2',
        'costo_unitario_estudiantes' => 'decimal:2',
        'costo_total_estudiantes' => 'decimal:2',
        'cantidad_movilizacion' => 'decimal:2',
        'costo_unitario_movilizacion' => 'decimal:2',
        'costo_total_movilizacion' => 'decimal:2',
        'cantidad_utiles' => 'decimal:2',
        'costo_unitario_utiles' => 'decimal:2',
        'costo_total_utiles' => 'decimal:2',
        'cantidad_impresion' => 'decimal:2',
        'costo_unitario_impresion' => 'decimal:2',
        'costo_total_impresion' => 'decimal:2',
        'cantidad_infraestructura' => 'decimal:2',
        'costo_unitario_infraestructura' => 'decimal:2',
        'costo_total_infraestructura' => 'decimal:2',
        'cantidad_servicios' => 'decimal:2',
        'costo_unitario_servicios' => 'decimal:2',
        'costo_total_servicios' => 'decimal:2'
    ];

    public function tipopresupuesto()
    {
        return $this->belongsTo(TipoPresupuesto::class, 'tipo_presupuesto_id',);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'administrador_id',);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    // Mutadores para convertir valores null a 0 - Docentes
    public function setCantidadHorasDocentesAttribute($value)
    {
        $this->attributes['cantidad_horas_docentes'] = $value ?? 0;
    }
    
    public function setCostoUnitarioDocentesAttribute($value)
    {
        $this->attributes['costo_unitario_docentes'] = $value ?? 0;
    }
    
    public function setCostoTotalDocentesAttribute($value)
    {
        $this->attributes['costo_total_docentes'] = $value ?? 0;
    }

    // Mutadores para convertir valores null a 0 - Estudiantes
    public function setCantidadHorasEstudiantesAttribute($value)
    {
        $this->attributes['cantidad_horas_estudiantes'] = $value ?? 0;
    }
    
    public function setCostoUnitarioEstudiantesAttribute($value)
    {
        $this->attributes['costo_unitario_estudiantes'] = $value ?? 0;
    }
    
    public function setCostoTotalEstudiantesAttribute($value)
    {
        $this->attributes['costo_total_estudiantes'] = $value ?? 0;
    }

    // Mutadores para convertir valores null a 0 - Movilizacion
    public function setCantidadMovilizacionAttribute($value)
    {
        $this->attributes['cantidad_movilizacion'] = $value ?? 0;
    }
    
    public function setCostoUnitarioMovilizacionAttribute($value)
    {
        $this->attributes['costo_unitario_movilizacion'] = $value ?? 0;
    }
    
    public function setCostoTotalMovilizacionAttribute($value)
    {
        $this->attributes['costo_total_movilizacion'] = $value ?? 0;
    }

    // Mutadores para convertir valores null a 0 - Utiles
    public function setCantidadUtilesAttribute($value)
    {
        $this->attributes['cantidad_utiles'] = $value ?? 0;
    }
    
    public function setCostoUnitarioUtilesAttribute($value)
    {
        $this->attributes['costo_unitario_utiles'] = $value ?? 0;
    }
    
    public function setCostoTotalUtilesAttribute($value)
    {
        $this->attributes['costo_total_utiles'] = $value ?? 0;
    }

    // Mutadores para convertir valores null a 0 - Impresion
    public function setCantidadImpresionAttribute($value)
    {
        $this->attributes['cantidad_impresion'] = $value ?? 0;
    }
    
    public function setCostoUnitarioImpresionAttribute($value)
    {
        $this->attributes['costo_unitario_impresion'] = $value ?? 0;
    }
    
    public function setCostoTotalImpresionAttribute($value)
    {
        $this->attributes['costo_total_impresion'] = $value ?? 0;
    }

    // Mutadores para convertir valores null a 0 - Infraestructura
    public function setCantidadInfraestructuraAttribute($value)
    {
        $this->attributes['cantidad_infraestructura'] = $value ?? 0;
    }
    
    public function setCostoUnitarioInfraestructuraAttribute($value)
    {
        $this->attributes['costo_unitario_infraestructura'] = $value ?? 0;
    }
    
    public function setCostoTotalInfraestructuraAttribute($value)
    {
        $this->attributes['costo_total_infraestructura'] = $value ?? 0;
    }

    // Mutadores para convertir valores null a 0 - Servicios
    public function setCantidadServiciosAttribute($value)
    {
        $this->attributes['cantidad_servicios'] = $value ?? 0;
    }
    
    public function setCostoUnitarioServiciosAttribute($value)
    {
        $this->attributes['costo_unitario_servicios'] = $value ?? 0;
    }
    
    public function setCostoTotalServiciosAttribute($value)
    {
        $this->attributes['costo_total_servicios'] = $value ?? 0;
    }

    protected $table = 'presupuesto';
}
