<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'nombre_completo',
        'mail',
        'contrasenia',
        'rol',
        'estado',
        'fecha_alta',
        'cambiar_contrasenia',
    ];
}
