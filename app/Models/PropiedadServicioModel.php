<?php

namespace App\Models;

use CodeIgniter\Model;

class PropiedadServicioModel extends Model
{
    protected $table = 'propiedad_servicio';
    protected $primaryKey = 'id_propiedad_servicio';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_propiedad',
        'id_tipo_servicio',
        'codigo_pago_electronico',
        'numero_cuenta_contrato',
        'numero_medidor',
        'domicilio_facturacion',
        'titular',
        'estado',
    ];
}
