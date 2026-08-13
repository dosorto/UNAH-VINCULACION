<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodoAcademico extends Model
{
    use HasFactory;

    public const NOMBRES_BASE = [
        'Primer Periodo',
        'Segundo Periodo',
        'Tercer Periodo',
    ];

    protected $table = 'periodos_academicos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['nombre'];
}
