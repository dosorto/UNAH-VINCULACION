<?php

namespace App\Models\InformeFinal;

use Illuminate\Database\Eloquent\Model;

class InformeFinalBeneficiario extends Model
{
    protected $table = 'informe_final_beneficiarios';
    protected $guarded = ['id'];

    public function informe() { return $this->belongsTo(InformeFinalProyecto::class, 'informe_final_proyecto_id'); }
    public function getTotalSexoAttribute(): int { return $this->hombres + $this->mujeres; }
    public function getTotalEdadAttribute(): int { return collect(['edad_0_10', 'edad_11_18', 'edad_19_25', 'edad_26_35', 'edad_36_50', 'edad_51_65', 'edad_66_80', 'edad_81_mas'])->sum(fn ($campo) => (int) $this->{$campo}); }
    public function getTotalEtniaAttribute(): int { return collect(['indigena_hombres', 'indigena_mujeres', 'afrodescendiente_hombres', 'afrodescendiente_mujeres', 'mestizo_hombres', 'mestizo_mujeres'])->sum(fn ($campo) => (int) $this->{$campo}); }
    public function getTotalesConsistentesAttribute(): bool { return count(array_unique([$this->total_sexo, $this->total_edad, $this->total_etnia])) === 1; }
}
