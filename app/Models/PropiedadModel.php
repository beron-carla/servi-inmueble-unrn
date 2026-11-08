<?php

namespace App\Models;

use CodeIgniter\Model;

class PropiedadModel extends Model
{
    protected $table = 'propiedad';
    protected $primaryKey = 'id_propiedad';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'alias',
        'superficie',
        'estado',
        'id_tipo_inmueble',
        'id_propietario',
        'id_direccion',
    ];
}
