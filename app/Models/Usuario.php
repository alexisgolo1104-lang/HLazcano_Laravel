<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'correo',
        'telefono',
        'sueldo',
        'contrasena',
        'rol',
        'id_tienda',
        'activo',
        'debe_cambiar_contrasena',
        'creado_en',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'debe_cambiar_contrasena' => 'boolean',
        ];
    }

    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_tienda', 'id_tienda');
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }
}
