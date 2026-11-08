<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoInmuebleModel extends Model
{
    protected $table = 'tipo_inmueble';
    protected $primaryKey = 'id_tipo_inmueble';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nombre'];
}
