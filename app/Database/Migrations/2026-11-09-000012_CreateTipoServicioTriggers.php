<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTipoServicioTriggers extends Migration
{
    private const TRIGGER_BODY = <<<'SQL'
BEGIN
  IF NOT (
       (NEW.`tipo` = 'electricidad' AND NEW.`unidad_medida` IN ('kWh','Moneda fija'))
    OR (NEW.`tipo` = 'agua'         AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    OR (NEW.`tipo` = 'gas'          AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    OR (NEW.`tipo` = 'internet'     AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'telefonia'    AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'television'   AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'impuesto'     AND NEW.`unidad_medida` = 'Moneda fija')
    OR (NEW.`tipo` = 'expensas'     AND NEW.`unidad_medida` = 'Moneda fija')
    OR (NEW.`tipo` = 'otro'         AND NEW.`unidad_medida` IN ('kWh','m³','Gigas','Moneda fija'))
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Unidad de medida no compatible con el tipo de servicio';
  END IF;
END
SQL;

    public function up(): void
    {
        $this->db->query(
            'CREATE TRIGGER `trg_tipo_servicio_unidad_bi` BEFORE INSERT ON `tipo_servicio` FOR EACH ROW '
            . self::TRIGGER_BODY,
        );
        $this->db->query(
            'CREATE TRIGGER `trg_tipo_servicio_unidad_bu` BEFORE UPDATE ON `tipo_servicio` FOR EACH ROW '
            . self::TRIGGER_BODY,
        );
    }

    public function down(): void
    {
        $this->db->query('DROP TRIGGER IF EXISTS `trg_tipo_servicio_unidad_bu`');
        $this->db->query('DROP TRIGGER IF EXISTS `trg_tipo_servicio_unidad_bi`');
    }
}
