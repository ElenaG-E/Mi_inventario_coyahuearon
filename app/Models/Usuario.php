<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'rol_id',
        'estado',  
        'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    // Relación con rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    // Relación con asignaciones históricas
    public function asignaciones()
    {
        return $this->hasMany(Asignacion::class, 'usuario_id');
    }

    // Relación con movimientos
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'usuario_id');
    }

    // Relación inversa: equipos asociados directamente
    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'usuario_id');
    }

    // Relación inversa: insumos asociados directamente
    public function insumos()
    {
        return $this->hasMany(Insumo::class, 'usuario_id');
    }

    // Este método define qué campo se usa como "username" en el login
    public function username()
    {
        return 'email'; // asegúrate que coincida con tu columna real
    }
}