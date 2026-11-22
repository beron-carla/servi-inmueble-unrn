<?php

namespace Tests\Database;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class Hito2SchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testTransaccionHasHito2ColumnsAndIndexes(): void
    {
        $columnas = $this->db->getFieldNames('transaccion');

        foreach ([
            'estado',
            'id_admin_validador',
            'fecha_validacion',
            'motivo_rechazo',
            'comprobante_nombre',
            'comprobante_mime',
        ] as $columna) {
            $this->assertContains($columna, $columnas);
        }

        $indices = $this->db->query('SHOW INDEX FROM `transaccion`')->getResultArray();
        $nombres = array_column($indices, 'Key_name');

        $this->assertContains('uk_transaccion_factura_comprobante', $nombres);
        $this->assertContains('idx_transaccion_estado_pago', $nombres);
    }

    public function testExistingPaymentsAreBackfilledAsValidated(): void
    {
        $pago = $this->db->table('transaccion')
            ->where('estado', 'validada')
            ->get()
            ->getRowArray();

        $this->assertNotNull($pago);
        $this->assertSame('validada', $pago['estado']);
        $this->assertNotEmpty($pago['fecha_validacion']);
    }
}
