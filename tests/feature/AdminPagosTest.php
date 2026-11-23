<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use DateTimeImmutable;

final class AdminPagosTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCannotAccessAdminPaymentValidation(): void
    {
        $propietario = $this->crearUsuario('propietario-admin-pagos@example.test', 'propietario');
        $this->iniciarSesion($propietario, 'propietario');

        $this->get('/admin/pagos')->assertStatus(403);
    }

    public function testAdminCanValidatePaymentAndNotifyOwner(): void
    {
        [$propietario, $idFactura, $idPago] = $this->crearPagoPendiente();
        $admin = $this->crearUsuario('admin-validar-pagos@example.test', 'admin');
        $this->iniciarSesion($admin, 'admin');

        $this->postConCsrf('/admin/pagos/' . $idPago . '/validar', [])
            ->assertRedirectTo('/admin/pagos');

        $pago = $this->db->table('transaccion')
            ->where('id_transaccion', $idPago)
            ->get()
            ->getRowArray();
        $factura = $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();

        $this->assertSame('validada', $pago['estado']);
        $this->assertSame($admin, (int) $pago['id_admin_validador']);
        $this->assertNotNull($pago['fecha_validacion']);
        $this->assertSame('pagada', $factura['estado']);

        $notificacion = $this->db->table('notificacion')
            ->where('id_usuario', $propietario)
            ->where('id_transaccion', $idPago)
            ->where('tipo', 'pago_validado')
            ->get()
            ->getRowArray();
        $this->assertNotEmpty($notificacion);

        $this->postConCsrf('/admin/pagos/' . $idPago . '/validar', [])
            ->assertRedirectTo('/admin/pagos');
        $this->assertSame(
            'El pago ya fue procesado',
            session()->getFlashdata('error'),
        );
    }

    public function testRejectRequiresReasonAndKeepsInvoicePending(): void
    {
        [, $idFactura, $idPago] = $this->crearPagoPendiente();
        $admin = $this->crearUsuario('admin-rechazar-pagos@example.test', 'admin');
        $this->iniciarSesion($admin, 'admin');

        $this->postConCsrf('/admin/pagos/' . $idPago . '/rechazar', ['motivo' => ''])
            ->assertRedirectTo('/admin/pagos/' . $idPago);
        $this->assertSame(
            'El motivo del rechazo es obligatorio',
            session()->getFlashdata('rechazo_errores')['motivo'],
        );

        $this->postConCsrf('/admin/pagos/' . $idPago . '/rechazar', [
            'motivo' => 'Comprobante ilegible',
        ]);

        $pago = $this->db->table('transaccion')
            ->where('id_transaccion', $idPago)
            ->get()
            ->getRowArray();
        $factura = $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();

        $this->assertSame('rechazada', $pago['estado']);
        $this->assertSame('Comprobante ilegible', $pago['motivo_rechazo']);
        $this->assertSame('pendiente', $factura['estado']);
    }

    public function testAdminCanFilterAndViewPaymentDetail(): void
    {
        [, , $idPago] = $this->crearPagoPendiente();
        $admin = $this->crearUsuario('admin-listado-pagos@example.test', 'admin');
        $this->iniciarSesion($admin, 'admin');

        $listado = $this->get('/admin/pagos?estado=pendiente');
        $listado->assertStatus(200);
        $listado->assertSee('Pendiente de validación');

        $detalle = $this->get('/admin/pagos/' . $idPago);
        $detalle->assertStatus(200);
        $detalle->assertSee('Detalle del pago');
        $detalle->assertSee(
            'CP-' . (new DateTimeImmutable('today'))->format('Ymd') . '-000001',
        );
    }

    private function postConCsrf(string $uri, array $datos): \CodeIgniter\Test\TestResponse
    {
        $idPago = (int) preg_match('/admin\/pagos\/([0-9]+)/', $uri, $matches) === 1
            ? (int) $matches[1]
            : 0;
        $pagina = $idPago > 0
            ? $this->get('/admin/pagos/' . $idPago)
            : $this->get('/admin/pagos');
        $pagina->assertStatus(200);
        preg_match(
            '/name="csrf_test_name"\s+value="([^"]+)"/',
            $pagina->response()->getBody(),
            $matches,
        );
        $this->assertNotEmpty($matches[1] ?? null);
        $this->withSession($_SESSION);

        return $this->post(
            $uri,
            array_merge($datos, [
                'csrf_test_name' => html_entity_decode($matches[1], ENT_QUOTES),
            ]),
        );
    }

    private function crearPagoPendiente(): array
    {
        $propietario = $this->crearUsuario(
            'owner-hu18-' . bin2hex(random_bytes(4)) . '@example.test',
            'propietario',
        );
        $this->db->table('direccion')->insert([
            'calle' => 'San Martín',
            'numero' => 200,
            'ciudad' => 'Bariloche',
        ]);
        $idDireccion = (int) $this->db->insertID();
        $idTipo = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'departamento')
            ->get()
            ->getRow()
            ->id_tipo_inmueble;
        $this->db->table('propiedad')->insert([
            'alias' => 'Departamento HU-18',
            'superficie' => 75,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $idTipo,
            'id_propietario' => $propietario,
            'id_direccion' => $idDireccion,
        ]);
        $idPropiedad = (int) $this->db->insertID();
        $tipo = $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('activo', 1)
            ->get()
            ->getRow();
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => (int) $tipo->id_tipo_servicio,
            'codigo_pago_electronico' => 'CPE-HU18',
            'numero_cuenta_contrato' => 'CTA-HU18',
            'numero_medidor' => null,
            'domicilio_facturacion' => 'San Martín 200',
            'titular' => 'Titular HU-18',
            'estado' => 'activo',
        ]);
        $idServicio = (int) $this->db->insertID();
        $hoy = new DateTimeImmutable('today');
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => $hoy->format('m'),
            'periodo_anio' => (int) $hoy->format('Y'),
            'fecha_emision' => $hoy->format('Y-m-d'),
            'fecha_primer_vencimiento' => $hoy->modify('-10 days')->format('Y-m-d'),
            'fecha_segundo_vencimiento' => $hoy->modify('-5 days')->format('Y-m-d'),
            'monto_total' => '1500.00',
            'consumo_del_periodo' => 0,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor HU-18',
            'categoria_factura' => 'privado',
            'estado' => 'pendiente',
        ]);
        $idFactura = (int) $this->db->insertID();
        $this->db->table('transaccion')->insert([
            'id_factura' => $idFactura,
            'id_propietario' => $propietario,
            'fecha_pago' => $hoy->format('Y-m-d'),
            'monto_abonado' => '1500.00',
            'medio_pago' => 'transferencia',
            'numero_comprobante' => 'CP-' . $hoy->format('Ymd') . '-000001',
            'comprobante_digital' => "%PDF-1.4\n%%EOF",
            'comprobante_nombre' => 'comprobante.pdf',
            'comprobante_mime' => 'application/pdf',
            'en_mora' => 0,
            'estado' => 'pendiente',
        ]);

        return [$propietario, $idFactura, (int) $this->db->insertID()];
    }

    private function crearUsuario(string $mail, string $rol): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario HU-18',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function iniciarSesion(int $idUsuario, string $rol): void
    {
        $token = bin2hex(random_bytes(32));
        $this->db->table('sesion')->insert([
            'id_usuario' => $idUsuario,
            'token' => $token,
        ]);
        $this->withSession([
            'id_usuario' => $idUsuario,
            'id_sesion' => (int) $this->db->insertID(),
            'token' => $token,
            'rol' => $rol,
            'nombre_completo' => 'Usuario HU-18',
        ]);
    }
}
