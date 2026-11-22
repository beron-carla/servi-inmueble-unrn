<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTransaccionHito2 extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('transaccion', [
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['pendiente', 'validada', 'rechazada'],
                'null' => false,
                'default' => 'pendiente',
            ],
            'id_admin_validador' => [
                'type' => 'INT',
                'null' => true,
            ],
            'fecha_validacion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'motivo_rechazo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'comprobante_nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'comprobante_mime' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
        ]);

        $this->db->query(
            'ALTER TABLE `transaccion` '
            . 'ADD CONSTRAINT `fk_transaccion_admin_validador` '
            . 'FOREIGN KEY (`id_admin_validador`) REFERENCES `usuario` (`id_usuario`) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
        $this->db->query(
            'ALTER TABLE `transaccion` '
            . 'ADD UNIQUE KEY `uk_transaccion_factura_comprobante` '
            . '(`id_factura`, `numero_comprobante`)',
        );
        $this->db->query(
            'ALTER TABLE `transaccion` '
            . 'ADD KEY `idx_transaccion_estado_pago` (`estado`, `fecha_pago`)',
        );

        $this->db->query(
            "UPDATE `transaccion` "
            . "SET `estado` = 'validada', "
            . "`fecha_validacion` = CONCAT(`fecha_pago`, ' 00:00:00') "
            . "WHERE `estado` = 'pendiente'",
        );
    }

    public function down(): void
    {
        $this->db->query(
            'ALTER TABLE `transaccion` '
            . 'DROP INDEX `idx_transaccion_estado_pago`',
        );
        $this->db->query(
            'ALTER TABLE `transaccion` '
            . 'DROP INDEX `uk_transaccion_factura_comprobante`',
        );
        $this->db->query(
            'ALTER TABLE `transaccion` '
            . 'DROP FOREIGN KEY `fk_transaccion_admin_validador`',
        );
        $this->forge->dropColumn('transaccion', [
            'estado',
            'id_admin_validador',
            'fecha_validacion',
            'motivo_rechazo',
            'comprobante_nombre',
            'comprobante_mime',
        ]);
    }
}
