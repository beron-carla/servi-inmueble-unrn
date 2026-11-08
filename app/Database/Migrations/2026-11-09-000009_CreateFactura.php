<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFactura extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `factura` (
  `id_factura` int NOT NULL AUTO_INCREMENT,
  `id_propiedad_servicio` int NOT NULL,
  `periodo` ENUM('mes','anio') NOT NULL DEFAULT 'mes' COMMENT 'granularidad del período',
  `periodo_mes` ENUM('00','01','02','03','04','05','06','07','08','09','10','11','12') NOT NULL DEFAULT '00' COMMENT '00 = período anual',
  `periodo_anio` year NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_primer_vencimiento` date NOT NULL,
  `fecha_segundo_vencimiento` date DEFAULT NULL,
  `monto_total` decimal(12,2) NOT NULL,
  `consumo_del_periodo` int NOT NULL,
  `lectura_anterior` int DEFAULT NULL,
  `lectura_actual` int DEFAULT NULL,
  `unidad_medida` varchar(15) NOT NULL COMMENT 'valor historico al momento de la factura',
  `proveedor` varchar(45) NOT NULL COMMENT 'valor historico al momento de la factura',
  `categoria_factura` ENUM('publico','privado') NOT NULL COMMENT 'valor historico al momento de la factura',
  `estado` ENUM('pendiente','pagada') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id_factura`),
  UNIQUE KEY `index_factura_periodo` (`id_propiedad_servicio`,`periodo`,`periodo_anio`,`periodo_mes`),
  CONSTRAINT `chk_factura_periodo` CHECK (
    (`periodo` = 'mes' AND `periodo_mes` <> '00') OR
    (`periodo` = 'anio' AND `periodo_mes` = '00')
  ),
  CONSTRAINT `fk_factura_propiedad_servicio` FOREIGN KEY (`id_propiedad_servicio`) REFERENCES `propiedad_servicio` (`id_propiedad_servicio`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `factura`');
    }
}
