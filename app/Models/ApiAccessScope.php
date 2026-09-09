<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApiAccessScope extends Model { protected $table='api_access_scopes'; protected $fillable=['codigo','nombre','activo']; protected $casts=['activo'=>'boolean']; }
