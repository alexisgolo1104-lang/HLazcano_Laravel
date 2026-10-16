<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogRespaldo extends Model
{
    protected $table = 'logs_respaldo';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombre_archivo',
        'tamano_bytes',
        'tipo_operacion',
        'exito',
        'notas',
    ];
}
