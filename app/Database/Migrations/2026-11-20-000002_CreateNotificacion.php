<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificacion extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_notificacion' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'id_usuario' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'id_factura' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'id_transaccion' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'tipo' => [
                'type' => 'ENUM',
                'constraint' => ['por_vencer', 'vencida', 'pago_validado', 'pago_rechazado'],
                'null' => false,
            ],
            'mensaje' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'leida' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
            ],
            'fecha_creacion' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'idempotencia_key' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id_notificacion', true);
        $this->forge->addUniqueKey(['id_usuario', 'idempotencia_key'], 'uk_notificacion_idempotencia');
        $this->forge->addKey(['id_usuario', 'leida', 'fecha_creacion'], false, false, 'idx_notificacion_usuario_leida');
        $this->forge->addKey(['id_usuario', 'id_factura', 'tipo']);
        $this->forge->addKey(['id_usuario', 'id_transaccion', 'tipo']);
        $this->forge->createTable('notificacion', true);

        $this->db->query(
            'ALTER TABLE `notificacion` '
            . 'MODIFY `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        );

        $this->db->query(
            'ALTER TABLE `notificacion` '
            . 'ADD CONSTRAINT `fk_notificacion_usuario` '
            . 'FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
        $this->db->query(
            'ALTER TABLE `notificacion` '
            . 'ADD CONSTRAINT `fk_notificacion_factura` '
            . 'FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
        $this->db->query(
            'ALTER TABLE `notificacion` '
            . 'ADD CONSTRAINT `fk_notificacion_transaccion` '
            . 'FOREIGN KEY (`id_transaccion`) REFERENCES `transaccion` (`id_transaccion`) '
            . 'ON UPDATE RESTRICT ON DELETE RESTRICT',
        );
    }

    public function down(): void
    {
        $this->forge->dropTable('notificacion', true);
    }
}
