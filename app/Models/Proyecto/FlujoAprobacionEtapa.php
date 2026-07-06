<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class FlujoAprobacionEtapa extends Model
{
    use HasFactory;

    public const ALCANCE_SIN_FILTRO = 'SIN_FILTRO';
    public const ALCANCE_GLOBAL = 'GLOBAL';
    public const ALCANCE_CENTRO = 'CENTRO';
    public const ALCANCE_DEPARTAMENTO = 'DEPARTAMENTO';
    public const ALCANCE_CARRERA = 'CARRERA';
    public const ALCANCE_PROYECTO = 'PROYECTO';

    public const MULTIPLICIDAD_UNICO = 'UNICO';
    public const MULTIPLICIDAD_POR_CADA_UNIDAD = 'POR_CADA_UNIDAD';

    protected $table = 'flujos_aprobacion_etapas';

    protected $fillable = [
        'flujo_aprobacion_id',
        'orden',
        'codigo',
        'aplica_inscripcion',
        'aplica_informe_intermedio',
        'aplica_cierre_proyecto',
        'nombre',
        'tipo_etapa',
        'rol_revisor_id',
        'usuario_responsable_id',
        'cargo_firma_id',
        'requiere_asignacion',
        'emisor_define_destinatario',
        'activo',
        'estado_resultante',
        'permite_edicion',
        'permite_rechazo',
        'es_estado_final_aprobado',
        'alcance_academico',
        'multiplicidad_revision',
    ];

    protected $casts = [
        'aplica_inscripcion' => 'boolean',
        'aplica_informe_intermedio' => 'boolean',
        'aplica_cierre_proyecto' => 'boolean',
        'requiere_asignacion' => 'boolean',
        'emisor_define_destinatario' => 'boolean',
        'activo' => 'boolean',
        'permite_edicion' => 'boolean',
        'permite_rechazo' => 'boolean',
        'es_estado_final_aprobado' => 'boolean',
    ];

    public static function alcancesAcademicosDisponibles(): array
    {
        return [
            self::ALCANCE_SIN_FILTRO => 'Sin filtro académico',
            self::ALCANCE_GLOBAL => 'Global / Institucional',
            self::ALCANCE_CENTRO => 'Centro o facultad',
            self::ALCANCE_DEPARTAMENTO => 'Departamento académico',
            self::ALCANCE_CARRERA => 'Carrera',
            self::ALCANCE_PROYECTO => 'Responsable del proyecto',
        ];
    }

    public static function multiplicidadesRevisionDisponibles(): array
    {
        return [
            self::MULTIPLICIDAD_UNICO => 'Un único revisor',
            self::MULTIPLICIDAD_POR_CADA_UNIDAD => 'Un revisor por cada unidad',
        ];
    }

    public function tieneAlcanceAcademicoValido(): bool
    {
        return array_key_exists((string) $this->alcance_academico, self::alcancesAcademicosDisponibles());
    }

    public function tieneMultiplicidadRevisionValida(): bool
    {
        return array_key_exists((string) $this->multiplicidad_revision, self::multiplicidadesRevisionDisponibles());
    }

    public function requiereFiltroAcademico(): bool
    {
        return in_array($this->alcance_academico, [
            self::ALCANCE_CENTRO,
            self::ALCANCE_DEPARTAMENTO,
            self::ALCANCE_CARRERA,
        ], true);
    }

    public function requiereRevisionPorCadaUnidad(): bool
    {
        return $this->multiplicidad_revision === self::MULTIPLICIDAD_POR_CADA_UNIDAD;
    }

    public function flujo(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function cargoFirma(): BelongsTo
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_id');
    }

    public function rolRevisor(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_revisor_id');
    }

    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_responsable_id');
    }
}
