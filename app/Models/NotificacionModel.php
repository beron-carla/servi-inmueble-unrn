<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificacionModel extends Model
{
    protected $table = 'notificacion';
    protected $primaryKey = 'id_notificacion';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_usuario',
        'id_factura',
        'id_transaccion',
        'tipo',
        'mensaje',
        'leida',
        'fecha_creacion',
        'idempotencia_key',
    ];
}
