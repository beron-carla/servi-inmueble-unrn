<?php

namespace App\Models;

use CodeIgniter\Model;

class DireccionModel extends Model
{
    protected $table = 'direccion';
    protected $primaryKey = 'id_direccion';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['calle', 'numero', 'piso', 'depto', 'ciudad'];
}
