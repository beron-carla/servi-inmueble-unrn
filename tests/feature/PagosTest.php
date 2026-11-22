<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use DateTimeImmutable;

final class PagosTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCanRegisterCashPaymentAndInvoiceRemainsPending(): void
    {
        [$propietario, $idFactura] = $this->crearContextoFactura('pendiente');
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->postPago($idFactura, [
            'fecha_pago' => (new DateTimeImmutable('today'))->format('Y-m-d'),
            'monto_abonado' => '1250.50',
            'medio_pago' => 'efectivo',
            'numero_comprobante' => 'EFECTIVO001',
        ]);

        $respuesta->assertRedirectTo('/pagos');
        $pago = $this->db->table('transaccion')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();
        $factura = $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();

        $this->assertSame('pendiente', $pago['estado']);
        $this->assertNull($pago['comprobante_digital']);
        $this->assertSame('pendiente', $factura['estado']);
        $this->assertStringContainsString(
            'Pago registrado. Queda pendiente de validación.',
            (string) session()->getFlashdata('success'),
        );
        $this->assertSame(1, (int) $pago['en_mora']);

        $duplicado = $this->postPago($idFactura, [
            'fecha_pago' => (new DateTimeImmutable('today'))->format('Y-m-d'),
            'monto_abonado' => '1250.50',
            'medio_pago' => 'efectivo',
            'numero_comprobante' => 'EFECTIVO002',
        ]);
        $duplicado->assertRedirectTo('/facturas/' . $idFactura . '/pagar');
        $this->assertSame(
            'Ya existe un pago pendiente de validación para esta factura',
            session()->getFlashdata('pago_errores')['pendiente'],
        );
    }

    public function testOwnerCannotPayOtherOwnersInvoice(): void
    {
        [, $idFactura] = $this->crearContextoFactura('pendiente');
        $otro = $this->crearUsuario('otro@example.test', 'propietario');
        $this->iniciarSesion($otro, 'propietario');

        $respuesta = $this->get('/facturas/' . $idFactura . '/pagar');

        $respuesta->assertStatus(403);
        $this->assertStringContainsString(
            'No tiene permisos para esta acción',
            $respuesta->response()->getBody(),
        );
    }

    public function testPaidInvoiceAndDifferentAmountAreRejected(): void
    {
        [$propietario, $idPagada] = $this->crearContextoFactura('pagada');
        $this->iniciarSesion($propietario, 'propietario');

        $this->get('/facturas/' . $idPagada . '/pagar')
            ->assertRedirectTo('/facturas');
        $this->assertSame(
            'La factura ya está pagada',
            session()->getFlashdata('error'),
        );

        [$propietarioPendiente, $idPendiente] = $this->crearContextoFactura('pendiente');
        $this->iniciarSesion($propietarioPendiente, 'propietario');
        $respuesta = $this->postPago($idPendiente, [
            'fecha_pago' => (new DateTimeImmutable('today'))->format('Y-m-d'),
            'monto_abonado' => '100.00',
            'medio_pago' => 'efectivo',
            'numero_comprobante' => 'MONTODIST1',
        ]);

        $respuesta->assertRedirectTo('/facturas/' . $idPendiente . '/pagar');
        $this->assertSame(
            'El monto debe ser igual al total de la factura',
            session()->getFlashdata('pago_errores')['monto_abonado'],
        );
    }

    public function testFutureDateDuplicatePaymentAndMandatoryReceiptAreRejected(): void
    {
        [$propietario, $idFactura] = $this->crearContextoFactura('pendiente');
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->postPago($idFactura, [
            'fecha_pago' => (new DateTimeImmutable('tomorrow'))->format('Y-m-d'),
            'monto_abonado' => '1250.50',
            'medio_pago' => 'efectivo',
            'numero_comprobante' => 'FUTURA001',
        ]);
        $this->assertSame(
            'La fecha de pago no puede ser futura',
            session()->getFlashdata('pago_errores')['fecha_pago'],
        );

        $respuesta = $this->postPago($idFactura, [
            'fecha_pago' => (new DateTimeImmutable('today'))->format('Y-m-d'),
            'monto_abonado' => '1250.50',
            'medio_pago' => 'transferencia',
            'numero_comprobante' => 'TRANSFER1',
        ]);
        $respuesta->assertRedirectTo('/facturas/' . $idFactura . '/pagar');
        $this->assertSame(
            'El comprobante es obligatorio para este medio de pago',
            session()->getFlashdata('pago_errores')['comprobante'],
        );
    }

    public function testComprobanteIsOnlyAccessibleToOwnerOrAdmin(): void
    {
        [$propietario, $idFactura] = $this->crearContextoFactura('pendiente');
        $this->db->table('transaccion')->insert([
            'id_factura' => $idFactura,
            'id_propietario' => $propietario,
            'fecha_pago' => (new DateTimeImmutable('today'))->format('Y-m-d'),
            'monto_abonado' => '1250.50',
            'medio_pago' => 'transferencia',
            'numero_comprobante' => 'DESCARGA001',
            'comprobante_digital' => "%PDF-1.4\n%%EOF",
            'comprobante_nombre' => 'comprobante.pdf',
            'comprobante_mime' => 'application/pdf',
            'en_mora' => 0,
            'estado' => 'pendiente',
        ]);
        $idPago = (int) $this->db->insertID();

        $this->iniciarSesion($propietario, 'propietario');
        $propia = $this->get('/pagos/' . $idPago . '/comprobante');
        $propia->assertStatus(200);
        $this->assertSame(
            'application/pdf',
            $propia->response()->getHeaderLine('Content-Type'),
        );
        $this->assertSame(
            'nosniff',
            $propia->response()->getHeaderLine('X-Content-Type-Options'),
        );

        $otro = $this->crearUsuario('otro-descarga@example.test', 'propietario');
        $this->iniciarSesion($otro, 'propietario');
        $this->get('/pagos/' . $idPago . '/comprobante')->assertStatus(403);

        $admin = $this->crearUsuario('admin-descarga@example.test', 'admin');
        $this->iniciarSesion($admin, 'admin');
        $this->get('/pagos/' . $idPago . '/comprobante')->assertStatus(200);
    }

    private function crearContextoFactura(string $estado): array
    {
        $propietario = $this->crearUsuario(
            'pagos-' . bin2hex(random_bytes(4)) . '@example.test',
            'propietario',
        );
        $this->db->table('direccion')->insert([
            'calle' => 'Mitre',
            'numero' => 100,
            'ciudad' => 'Roca',
        ]);
        $idDireccion = (int) $this->db->insertID();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRow()->id_tipo_inmueble;
        $this->db->table('propiedad')->insert([
            'alias' => 'Propiedad pago',
            'superficie' => 60,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $idTipoInmueble,
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
            'codigo_pago_electronico' => 'CPE-PAGO',
            'numero_cuenta_contrato' => 'CTA-PAGO',
            'numero_medidor' => null,
            'domicilio_facturacion' => 'Mitre 100',
            'titular' => 'Titular',
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
            'monto_total' => '1250.50',
            'consumo_del_periodo' => 0,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'privado',
            'estado' => $estado,
        ]);

        return [$propietario, (int) $this->db->insertID()];
    }

    private function crearUsuario(string $mail, string $rol): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario pagos',
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
        ]);
    }

    private function postPago(int $idFactura, array $datos): \CodeIgniter\Test\TestResponse
    {
        $formulario = $this->get('/facturas/' . $idFactura . '/pagar');
        $formulario->assertStatus(200);
        preg_match(
            '/name="csrf_test_name"\s+value="([^"]+)"/',
            $formulario->response()->getBody(),
            $matches,
        );
        $this->assertNotEmpty($matches[1] ?? null);
        $this->withSession($_SESSION);

        return $this->post(
            '/facturas/' . $idFactura . '/pagos',
            array_merge($datos, [
                'csrf_test_name' => html_entity_decode($matches[1], ENT_QUOTES),
            ]),
        );
    }
}
