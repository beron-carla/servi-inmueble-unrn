<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateModificacionUsuario extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `modificacion_usuario` (
  `id_modificacion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL COMMENT 'usuario cuyos datos fueron modificados',
  `id_admin` int NOT NULL COMMENT 'usuario admin que realizo la modificacion',
  `fecha_modificacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_modificacion`),
  KEY `index_modificacion_usuario` (`id_usuario`,`fecha_modificacion`),
  KEY `index_modificacion_admin` (`id_admin`),
  CONSTRAINT `fk_modificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_modificacion_admin` FOREIGN KEY (`id_admin`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `modificacion_usuario`');
    }
}
