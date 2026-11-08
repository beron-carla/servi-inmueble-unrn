<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoricoLoginModel extends Model
{
    protected $table = 'historico_login';
    protected $primaryKey = 'id_intento';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['email', 'ip', 'exitoso', 'fecha'];
}
