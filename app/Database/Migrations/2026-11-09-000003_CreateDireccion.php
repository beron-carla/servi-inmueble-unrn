<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDireccion extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `direccion` (
  `id_direccion` int NOT NULL AUTO_INCREMENT,
  `calle` varchar(45) NOT NULL,
  `numero` int NOT NULL,
  `piso` int DEFAULT NULL,
  `depto` varchar(10) DEFAULT NULL,
  `ciudad` varchar(60) NOT NULL,
  PRIMARY KEY (`id_direccion`),
  KEY `index_calle_nro` (`calle`,`numero`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `direccion`');
    }
}
