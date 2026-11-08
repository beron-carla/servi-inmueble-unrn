<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTipoInmueble extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `tipo_inmueble` (
  `id_tipo_inmueble` int NOT NULL AUTO_INCREMENT,
  `nombre` ENUM('casa','departamento','local comercial','oficina','galpon') NOT NULL,
  PRIMARY KEY (`id_tipo_inmueble`),
  UNIQUE KEY `index_nombre_tipo_inmueble` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `tipo_inmueble`');
    }
}
