<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionModel extends Model
{
    protected $table = 'sesion';
    protected $primaryKey = 'id_sesion';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_usuario', 'token', 'fecha_creacion', 'ultima_actividad'];
}
