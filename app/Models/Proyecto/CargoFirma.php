<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\TipoCargoFirma;

class CargoFirma extends Model
{
    use HasFactory;

    protected $table = 'cargo_firma';

    protected $fillable = [
        'id',
        'descripcion',
        'tipo_cargo_firma_id',
        'tipo_estado_id',
        'estado_siguiente_id'
    ];

    /**
     * Regla única para todos los módulos que firman en firma_proyecto: la firma
     * de quien registra o coordina (TipoCargoFirma::CARGOS_SIN_SELLO) no lleva
     * sello; las firmas de etapas administrativas sí.
     */
    public static function admiteSello(?int $cargoFirmaId): bool
    {
        if (! $cargoFirmaId) {
            return true;
        }

        return ! static::query()
            ->whereKey($cargoFirmaId)
            ->whereHas('tipoCargoFirma', fn ($query) => $query->whereIn('nombre', TipoCargoFirma::CARGOS_SIN_SELLO))
            ->exists();
    }

    // recuperar el tipo de cargo de firma asociado con este cargo de firma :)
    public function tipoCargoFirma()
    {
        // clase, llave foranea, llave primaria
        return $this->hasOne(TipoCargoFirma::class, 'id', 'tipo_cargo_firma_id');
    }

    // recuperar el estado del proyecto asociado con este cargo de firma :)
    public function estadoProyectoActual()
    {
        // clase, llave foranea, llave primaria
        return $this->hasOne(TipoEstado::class, 'id', 'tipo_estado_id');
    }

    // recuperar el estado del proyecto asociado con este cargo de firma :)
    public function estadoProyectoSiguiente()
    {
        // clase, llave foranea, llave primaria
        return $this->hasOne(TipoEstado::class, 'id', 'estado_proyecto_id');
    }

    public function cargoFirmaAnterior()
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_anterior_id');
    }

    // recuperar el cargo de firma siguiente
    public function cargoFirmaSiguiente()
    {
        // ES EL CARGO DE FIRMA QUE TIENE COMO CARGO FIRMA ANTERIOR EL ID DE ESTE CARGO DE FIRMA
        return $this->hasOne(CargoFirma::class, 'cargo_firma_anterior_id', 'id');
    }

    
    
}
