<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePropiedad extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `propiedad` (
  `id_propiedad` int NOT NULL AUTO_INCREMENT,
  `alias` varchar(45) NOT NULL,
  `superficie` decimal(10,2) NOT NULL,
  `estado` ENUM('ocupado','disponible','en mantenimiento','inactivo') NOT NULL COMMENT 'inactivo = baja lógica; no se muestra en el frontend',
  `id_tipo_inmueble` int NOT NULL,
  `id_propietario` int NOT NULL,
  `id_direccion` int NOT NULL,
  PRIMARY KEY (`id_propiedad`),
  UNIQUE KEY `index_alias_propietario` (`id_propietario`,`alias`),
  KEY `fk_tipo_inmueble` (`id_tipo_inmueble`),
  KEY `fk_direccion` (`id_direccion`),
  CONSTRAINT `fk_duenio_inmueble` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tipo_inmueble` FOREIGN KEY (`id_tipo_inmueble`) REFERENCES `tipo_inmueble` (`id_tipo_inmueble`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `propiedad`');
    }
}
