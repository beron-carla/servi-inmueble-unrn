<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTipoServicio extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `tipo_servicio` (
  `id_tipo_servicio` int NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('agua','gas','electricidad','internet','telefonia','television','impuesto','expensas','otro') NOT NULL,
  `categoria` ENUM('publico','privado') NOT NULL,
  `unidad_medida` ENUM('kWh','m³','Gigas','Moneda fija') NOT NULL,
  `proveedor_predeterminado` varchar(45) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 cuando es bloqueado/dado de baja',
  PRIMARY KEY (`id_tipo_servicio`),
  UNIQUE KEY `index_tipo_proveedor` (`tipo`,`proveedor_predeterminado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `tipo_servicio`');
    }
}
