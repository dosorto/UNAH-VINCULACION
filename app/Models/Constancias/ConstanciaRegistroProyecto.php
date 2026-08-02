<?php

namespace App\Models\Constancias;

use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstanciaRegistroProyecto extends Model
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_EMITIDA = 'EMITIDA';
    public const ESTADO_ANULADA = 'ANULADA';
    public const ESTADO_ERROR = 'ERROR';

    protected $table = 'constancias_registro_proyecto';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'token_cifrado', 'snapshot'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'fecha_emision' => 'datetime',
            'anulada_en' => 'datetime',
            'version' => 'integer',
            'anio' => 'integer',
            'correlativo' => 'integer',
        ];
    }

    public function proyecto(): BelongsTo { return $this->belongsTo(Proyecto::class); }
    public function emisor(): BelongsTo { return $this->belongsTo(User::class, 'emitida_por'); }
    public function anulador(): BelongsTo { return $this->belongsTo(User::class, 'anulada_por'); }

    public function scopeEmitida(Builder $query): Builder { return $query->where('estado', self::ESTADO_EMITIDA); }
    public function scopeVigente(Builder $query): Builder { return $query->where('estado', self::ESTADO_EMITIDA)->whereNull('anulada_en'); }
    public function scopeAnulada(Builder $query): Builder { return $query->where('estado', self::ESTADO_ANULADA); }

    public function puedeDescargarse(): bool
    {
        return $this->estado === self::ESTADO_EMITIDA && blank($this->anulada_en) && filled($this->ruta_archivo);
    }
}
