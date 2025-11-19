<?php
declare(strict_types=1);

namespace Src\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'email_usuario',
        'identificacion_tributaria_usuario',
        'password_usuario',
        'nombre_usuario',
        'apellido_usuario',
        'direccion_usuario',
        'cedula_usuario',
        'pais_usuario', 
        'departamento_usuario',
        'ciudad_usuario',
        'ip_usuario',
        'cargo_usuario',
        'tel_usuario',
        'autorizo_usuario',
        'tipo_usuario_fk',
        'estado_usuario',
        'token_usuario',
        'codigo_usuario',
        'asociacion_camara_archivo_usuario'
    ];

    protected $hidden = [
        'password_usuario',
        'token_usuario'
    ];
    // Relaciones
    public function tipoUsuario()
    {
        return $this->belongsTo(TipoUsuario::class, 'tipo_usuario_fk', 'slug_tipo');
    }
} 