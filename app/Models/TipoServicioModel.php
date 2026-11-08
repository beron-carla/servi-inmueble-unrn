<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoServicioModel extends Model
{
    protected $table = 'tipo_servicio';
    protected $primaryKey = 'id_tipo_servicio';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['tipo', 'categoria', 'unidad_medida', 'proveedor_predeterminado', 'activo'];
}
