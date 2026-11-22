<?php

namespace Tests\Unit\Services;

use App\Services\NotificacionService;
use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

final class NotificacionServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testFourPaymentAndDeadlineNotificationTypesAreIdempotent(): void
    {
        $idUsuario = $this->crearUsuario();
        $idFactura = $this->crearFactura($idUsuario);
        $service = new NotificacionService();

        $porVencer = $this->db->table('factura')->where('id_factura', $idFactura)->get()->getRowArray();
        $porVencer['id_propietario'] = $idUsuario;
        $porVencer['id_factura'] = $idFactura;

        $this->assertGreaterThanOrEqual(1, $service->registrarVencimientos('2026-11-22'));
        $this->assertSame(0, $service->registrarVencimientos('2026-11-22'));

        $idTransaccion = $this->crearTransaccion($idFactura, $idUsuario);
        $transaccion = ['id_transaccion' => $idTransaccion];
        $factura = ['id_factura' => $idFactura, 'id_propietario' => $idUsuario];

        $service->registrarPagoValidado($transaccion, $factura);
        $service->registrarPagoRechazado($transaccion, $factura, 'Comprobante ilegible');
        $service->registrarPagoValidado($transaccion, $factura);

        $tipos = $this->db->table('notificacion')
            ->select('tipo')
            ->orderBy('tipo', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertEqualsCanonicalizing(
            ['por_vencer', 'vencida', 'pago_validado', 'pago_rechazado'],
            array_column($tipos, 'tipo'),
        );
        $this->assertGreaterThanOrEqual(3, $this->db->table('notificacion')->countAllResults());
    }

    public function testConfigurableDeadlineWindowAndVencidaWindow(): void
    {
        $idUsuario = $this->crearUsuario();
        $idFactura = $this->crearFactura($idUsuario, '2026-11-23');
        $service = new NotificacionService();

        $this->assertGreaterThanOrEqual(1, $service->registrarVencimientos('2026-11-22'));
        $notificacion = $this->db->table('notificacion')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();
        $this->assertSame('por_vencer', $notificacion['tipo']);

        $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->update(['fecha_segundo_vencimiento' => '2026-11-21']);
        $this->db->table('notificacion')->truncate();

        $this->assertGreaterThanOrEqual(1, $service->registrarVencimientos('2026-11-22'));
        $notificacion = $this->db->table('notificacion')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();
        $this->assertSame('vencida', $notificacion['tipo']);
    }

    public function testMissingAndForeignNotificationResultsAreSeparated(): void
    {
        $idUsuario = $this->crearUsuario();
        $idOtro = $this->crearUsuario();
        $idFactura = $this->crearFactura($idUsuario);
        $this->db->table('notificacion')->insert([
            'id_usuario' => $idUsuario,
            'id_factura' => $idFactura,
            'tipo' => 'vencida',
            'mensaje' => 'La factura correspondiente se encuentra vencida',
            'leida' => 0,
            'fecha_creacion' => '2026-11-22 12:00:00',
            'idempotencia_key' => 'venc:' . $idFactura . ':vencida',
        ]);
        $idNotificacion = (int) $this->db->insertID();

        $service = new NotificacionService();
        $missing = $service->marcarLeida(999999999, $idUsuario, 'propietario');
        $foreign = $service->marcarLeida($idNotificacion, $idOtro, 'propietario');

        $this->assertTrue($missing['no_encontrado']);
        $this->assertTrue($foreign['sin_permiso']);
    }

    private function crearUsuario(): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Propietario Notificaciones',
            'mail' => 'notif-' . bin2hex(random_bytes(4)) . '@example.test',
            'contrasenia' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'rol' => 'propietario',
            'estado' => 'activo',
            'fecha_alta' => '2026-11-22',
            'cambiar_contrasenia' => 0,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearFactura(int $idUsuario, string $segundoVencimiento = '2026-11-29'): int
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Test',
            'numero' => '1',
            'ciudad' => 'Roca',
        ]);
        $idDireccion = (int) $this->db->insertID();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRow()
            ->id_tipo_inmueble;
        $this->db->table('propiedad')->insert([
            'alias' => 'Propiedad notif',
            'superficie' => 50,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $idUsuario,
            'id_direccion' => $idDireccion,
        ]);
        $idPropiedad = (int) $this->db->insertID();
        $tipo = $this->db->table('tipo_servicio')->select('id_tipo_servicio')->where('activo', 1)->get()->getRow();
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => (int) $tipo->id_tipo_servicio,
            'codigo_pago_electronico' => 'CPE-NOTIF',
            'numero_cuenta_contrato' => 'CTA-NOTIF',
            'domicilio_facturacion' => 'Test 1',
            'titular' => 'Titular Notif',
            'estado' => 'activo',
        ]);
        $idServicio = (int) $this->db->insertID();
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '11',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-11-01',
            'fecha_primer_vencimiento' => '2026-11-22',
            'fecha_segundo_vencimiento' => $segundoVencimiento,
            'monto_total' => '100.00',
            'consumo_del_periodo' => 10,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Test',
            'categoria_factura' => 'Publico',
            'estado' => 'pendiente',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearTransaccion(int $idFactura, int $idUsuario): int
    {
        $this->db->table('transaccion')->insert([
            'id_factura' => $idFactura,
            'id_propietario' => $idUsuario,
            'fecha_pago' => '2026-11-22',
            'monto_abonado' => '100.00',
            'medio_pago' => 'efectivo',
            'numero_comprobante' => 'NOTIF-' . bin2hex(random_bytes(3)),
            'en_mora' => 0,
            'estado' => 'validada',
        ]);

        return (int) $this->db->insertID();
    }
}
