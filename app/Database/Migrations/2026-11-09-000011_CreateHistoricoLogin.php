<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHistoricoLogin extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `historico_login` (
  `id_intento` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL COMMENT 'soporta IPv4 e IPv6',
  `exitoso` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = intento fallido, 1 = login exitoso',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_intento`),
  KEY `index_login_email_fecha` (`email`,`fecha`),
  KEY `index_login_ip_fecha` (`ip`,`fecha`),
  KEY `index_login_email_ip_fecha` (`email`,`ip`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `historico_login`');
    }
}
