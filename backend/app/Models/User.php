<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'dni',
        'telefono',
        'activo',
        'sucursal_id',
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    protected string $guard_name = 'sanctum';
}
