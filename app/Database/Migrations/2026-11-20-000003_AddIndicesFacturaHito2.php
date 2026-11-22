<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIndicesFacturaHito2 extends Migration
{
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE `factura` '
            . 'ADD KEY `idx_factura_estado_vencimientos` '
            . '(`estado`, `fecha_primer_vencimiento`, `fecha_segundo_vencimiento`)',
        );
        $this->db->query(
            'ALTER TABLE `factura` '
            . 'ADD KEY `idx_factura_periodo_servicio` '
            . '(`periodo_anio`, `periodo_mes`, `id_propiedad_servicio`)',
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `factura` DROP INDEX `idx_factura_periodo_servicio`',
        );
        $this->db->query(
            'ALTER TABLE `factura` DROP INDEX `idx_factura_estado_vencimientos`',
        );
    }
}
