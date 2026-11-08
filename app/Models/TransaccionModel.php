<?php

namespace App\Models;

use CodeIgniter\Model;

class TransaccionModel extends Model
{
    protected $table = 'transaccion';
    protected $primaryKey = 'id_transaccion';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_factura',
        'id_propietario',
        'fecha_pago',
        'monto_abonado',
        'medio_pago',
        'numero_comprobante',
        'comprobante_digital',
        'en_mora',
    ];
}
