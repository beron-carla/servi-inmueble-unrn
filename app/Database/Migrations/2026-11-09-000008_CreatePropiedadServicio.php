<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePropiedadServicio extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `propiedad_servicio` (
  `id_propiedad_servicio` int NOT NULL AUTO_INCREMENT,
  `id_propiedad` int NOT NULL,
  `id_tipo_servicio` int NOT NULL,
  `codigo_pago_electronico` varchar(20) NOT NULL,
  `numero_cuenta_contrato` varchar(20) NOT NULL,
  `numero_medidor` varchar(20) DEFAULT NULL COMMENT 'opcional: no todos los servicios tienen medidor',
  `domicilio_facturacion` varchar(45) NOT NULL,
  `titular` varchar(50) NOT NULL,
  `estado` ENUM('activo','pausado','en revision','inactivo') DEFAULT 'activo' COMMENT 'inactivo = baja lógica; NULL permitido',
  PRIMARY KEY (`id_propiedad_servicio`),
  KEY `fk_propiedad_servicio` (`id_propiedad`),
  KEY `fk_servicio_propiedad` (`id_tipo_servicio`),
  CONSTRAINT `fk_propiedad_servicio` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_servicio_propiedad` FOREIGN KEY (`id_tipo_servicio`) REFERENCES `tipo_servicio` (`id_tipo_servicio`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `propiedad_servicio`');
    }
}
