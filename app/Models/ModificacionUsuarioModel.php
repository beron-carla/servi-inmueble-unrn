<?php

namespace App\Models;

use CodeIgniter\Model;

class ModificacionUsuarioModel extends Model
{
    protected $table = 'modificacion_usuario';
    protected $primaryKey = 'id_modificacion';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_usuario', 'id_admin', 'fecha_modificacion'];
}
