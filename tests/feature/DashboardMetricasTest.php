<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class DashboardMetricasTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testAdminVeMetricasGlobalesYLaSerieDeDoceMeses(): void
    {
        $admin = $this->crearUsuario('admin', 'dashboard.admin@example.test');
        $propietario = $this->crearUsuario('propietario', 'dashboard.propietario@example.test');
        $this->crearDatosDashboard($propietario);
        $this->iniciarSesion($admin, 'admin');

        $respuesta = $this->get('/dashboard');

        $respuesta->assertOK();
        $cuerpo = $respuesta->response()->getBody();
        $this->assertStringContainsString('Usuarios activos', $cuerpo);
        $this->assertStringContainsString('Inmuebles activos', $cuerpo);
        $this->assertStringContainsString('Facturas pendientes', $cuerpo);
        $this->assertStringContainsString('Pagos pendientes de validación', $cuerpo);
        $this->assertStringContainsString('Recaudación del mes', $cuerpo);
        $this->assertStringContainsString('Evolución de los últimos 12 meses', $cuerpo);
    }

    public function testPropietarioNoRecibeMetricasDeUnInmuebleAjeno(): void
    {
        $propietario = $this->crearUsuario('propietario', 'dashboard.propio@example.test');
        $ajeno = $this->crearUsuario('propietario', 'dashboard.ajeno@example.test');
        $this->crearDatosDashboard($propietario);
        $this->crearDatosDashboard($ajeno);
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get('/dashboard?inmueble=999999');

        $respuesta->assertOK();
        $cuerpo = $respuesta->response()->getBody();
        $this->assertStringNotContainsString('Usuarios activos', $cuerpo);
        $this->assertStringContainsString('Notificaciones sin leer', $cuerpo);
        $this->assertStringContainsString('Inmuebles activos', $cuerpo);
    }

    public function testEstadoVacioYFormatosLocales(): void
    {
        $propietario = $this->crearUsuario('propietario', 'dashboard.vacio@example.test');
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get('/dashboard');

        $respuesta->assertOK();
        $cuerpo = $respuesta->response()->getBody();
        $this->assertStringContainsString('Sin datos', $cuerpo);
        helper('format');
        $this->assertSame('$ 1.234,50', formato_moneda(1234.5));
        $this->assertSame('12,5%', formato_porcentaje(12.5));
    }

    private function crearUsuario(string $rol, string $mail): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario Dashboard',
            'mail' => $mail,
            'contrasenia' => password_hash('Password1234', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
            'fecha_alta' => date('Y-m-d'),
            'cambiar_contrasenia' => 0,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearDatosDashboard(int $idPropietario): void
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Test',
            'numero' => 10,
            'ciudad' => 'Roca',
        ]);
        $idDireccion = (int) $this->db->insertID();

        $tipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->get()
            ->getRow()
            ->id_tipo_inmueble;
        $this->db->table('propiedad')->insert([
            'alias' => 'Dashboard ' . $idPropietario,
            'superficie' => 50,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $tipoInmueble,
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);
        $idPropiedad = (int) $this->db->insertID();

        $tipoServicio = (int) $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->get()
            ->getRow()
            ->id_tipo_servicio;
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => $tipoServicio,
            'codigo_pago_electronico' => 'COD-DASH-' . $idPropietario,
            'numero_cuenta_contrato' => 'CTA-DASH-' . $idPropietario,
            'domicilio_facturacion' => 'Test 10',
            'titular' => 'Usuario Dashboard',
            'estado' => 'activo',
        ]);
        $idPropiedadServicio = (int) $this->db->insertID();

        $hoy = date('Y-m-d');
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idPropiedadServicio,
            'periodo' => 'mes',
            'periodo_mes' => date('m'),
            'periodo_anio' => (int) date('Y'),
            'fecha_emision' => $hoy,
            'fecha_primer_vencimiento' => date('Y-m-d', strtotime('+15 days')),
            'fecha_segundo_vencimiento' => null,
            'monto_total' => '100.00',
            'consumo_del_periodo' => 10,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'publico',
            'estado' => 'pendiente',
        ]);
        $idFactura = (int) $this->db->insertID();

        $this->db->table('transaccion')->insert([
            'id_factura' => $idFactura,
            'id_propietario' => $idPropietario,
            'fecha_pago' => $hoy,
            'monto_abonado' => '100.00',
            'medio_pago' => 'efectivo',
            'numero_comprobante' => 'DASH-' . $idPropietario,
            'comprobante_digital' => null,
            'en_mora' => 0,
            'estado' => 'pendiente',
        ]);
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
            'nombre_completo' => 'Usuario Dashboard',
            'estado' => 'activo',
        ]);
    }
}
