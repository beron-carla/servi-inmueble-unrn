<?php

namespace App\Models;

use CodeIgniter\Model;

class FacturaModel extends Model
{
    protected $table = 'factura';
    protected $primaryKey = 'id_factura';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_propiedad_servicio',
        'periodo',
        'periodo_mes',
        'periodo_anio',
        'fecha_emision',
        'fecha_primer_vencimiento',
        'fecha_segundo_vencimiento',
        'monto_total',
        'consumo_del_periodo',
        'lectura_anterior',
        'lectura_actual',
        'unidad_medida',
        'proveedor',
        'categoria_factura',
        'estado',
    ];
}
