<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsuario extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(50) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `contrasenia` varchar(255) NOT NULL COMMENT 'hash (password_hash), nunca texto plano',
  `rol` ENUM('propietario','admin') NOT NULL DEFAULT 'propietario' COMMENT 'por defecto es propietario, a menos que un admin cree a otro admin',
  `estado` ENUM('activo','bloqueado','baja') NOT NULL DEFAULT 'activo' COMMENT 'Baja = baja lógica; Bloqueado = bloqueo administrativo',
  `fecha_alta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cambiar_contrasenia` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = debe cambiar la contrasenia (restauracion o temporal creada por admin)',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `index_mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `usuario`');
    }
}
