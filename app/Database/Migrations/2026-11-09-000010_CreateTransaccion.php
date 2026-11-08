<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransaccion extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `transaccion` (
  `id_transaccion` int NOT NULL AUTO_INCREMENT,
  `id_factura` int NOT NULL,
  `id_propietario` int NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto_abonado` decimal(12,2) NOT NULL,
  `medio_pago` ENUM('transferencia','debito automatico','efectivo','billetera virtual') NOT NULL,
  `numero_comprobante` varchar(50) NOT NULL COMMENT 'código/número de comprobante alfanumérico',
  `comprobante_digital` mediumblob DEFAULT NULL COMMENT 'archivo del comprobante (opcional)',
  `en_mora` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = pago a tiempo, 1 = pago despues del vencimiento',
  PRIMARY KEY (`id_transaccion`),
  KEY `fk_transaccion_propietario` (`id_propietario`),
  KEY `fk_transaccion_factura` (`id_factura`),
  CONSTRAINT `fk_transaccion_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_transaccion_propietario` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `transaccion`');
    }
}
