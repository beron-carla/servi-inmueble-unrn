<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use App\Services\ServicioInmuebleService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ServiciosInmuebleTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCanCreateAnActiveServiceWithoutAMeter(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.alta@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Depto de prueba', 'ocupado');
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles/' . $idPropiedad . '/servicios', [
            'csrf_test_name' => $csrf,
            'id_tipo_servicio' => $this->idTipoServicio('electricidad'),
            'codigo_pago_electronico' => 'CPE-ALTA-1',
            'numero_cuenta_contrato' => 'CTA-ALTA-1',
            'numero_medidor' => '',
            'domicilio_facturacion' => 'Belgrano 100',
            'titular' => 'Ana de prueba',
        ]);

        $servicio = $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRowArray();

        $this->assertNotNull($servicio);
        $this->assertSame('activo', $servicio['estado']);
        $this->assertNull($servicio['numero_medidor']);
        $respuesta->assertRedirectTo('/servicios-inmueble/' . $servicio['id_propiedad_servicio']);

        $detalle = $this->get('/servicios-inmueble/' . $servicio['id_propiedad_servicio']);
        $detalle->assertOK();
        $this->assertStringContainsString('Este servicio admite carga de facturas.', $detalle->response()->getBody());

        $inmueble = $this->get('/inmuebles/' . $idPropiedad);
        $this->assertStringContainsString('Ver servicio', $inmueble->response()->getBody());
        $this->assertStringContainsString('Vincular servicio', $inmueble->response()->getBody());
    }

    public function testRequiredContractFieldsUseAcceptanceMessages(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.validacion@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble validacion', 'disponible');
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles/' . $idPropiedad . '/servicios', [
            'csrf_test_name' => $csrf,
            'id_tipo_servicio' => '',
            'codigo_pago_electronico' => '',
            'numero_cuenta_contrato' => '',
            'numero_medidor' => '',
            'domicilio_facturacion' => '',
            'titular' => '',
        ]);
        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $errores = session()->getFlashdata('servicio_errores');

        $this->assertSame('El tipo de servicio es obligatorio', $errores['id_tipo_servicio']);
        $this->assertSame('El CPE/Pagar es obligatorio', $errores['codigo_pago_electronico']);
        $this->assertSame('El número de cuenta es obligatorio', $errores['numero_cuenta_contrato']);
        $this->assertSame('El domicilio de facturación es obligatorio', $errores['domicilio_facturacion']);
        $this->assertSame('El titular es obligatorio', $errores['titular']);
        $this->assertSame(0, $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedad)
            ->countAllResults());
    }

    public function testOwnerCannotAccessAnotherOwnersPropertyAndAdminCan(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.dueno@example.test');
        $idOtroPropietario = $this->crearUsuario('propietario', 'servicio.ajeno@example.test');
        $idPropiedadAjena = $this->crearPropiedad($idOtroPropietario, 'Inmueble ajeno', 'disponible');
        $idServicioAjeno = $this->crearServicio(
            $idPropiedadAjena,
            $this->idTipoServicio('gas'),
            'activo',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $denegado = $this->get('/inmuebles/' . $idPropiedadAjena . '/servicios/nuevo');
        $denegado->assertStatus(403);
        $this->assertStringContainsString('No tiene permisos para esta acción', $denegado->response()->getBody());
        $detalleAjeno = $this->get('/servicios-inmueble/' . $idServicioAjeno);
        $detalleAjeno->assertStatus(403);

        $idAdmin = $this->crearUsuario('admin', 'servicio.admin@example.test');
        $this->iniciarSesion($idAdmin, 'admin');
        $formulario = $this->get('/inmuebles/' . $idPropiedadAjena . '/servicios/nuevo');
        $formulario->assertOK();
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles/' . $idPropiedadAjena . '/servicios', [
            'csrf_test_name' => $csrf,
            'id_tipo_servicio' => $this->idTipoServicio('agua'),
            'codigo_pago_electronico' => 'CPE-ADMIN-1',
            'numero_cuenta_contrato' => 'CTA-ADMIN-1',
            'domicilio_facturacion' => 'Belgrano 100',
            'titular' => 'Titular de prueba',
        ]);

        $respuesta->assertRedirect();
        $this->assertSame(2, $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedadAjena)
            ->countAllResults());
    }

    public function testAValidLinkBlocksDuplicatesButAnInactiveLinkAllowsRelinking(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.duplicado@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble duplicado', 'disponible');
        $idTipo = $this->idTipoServicio('gas');
        $idServicio = $this->crearServicio($idPropiedad, $idTipo, 'activo');
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);
        $datos = $this->datosServicio($csrf, $idTipo);

        $duplicado = $this->post('/inmuebles/' . $idPropiedad . '/servicios', $datos);
        $duplicado->assertRedirectTo('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $this->assertSame(
            'El inmueble ya tiene este servicio vinculado',
            session()->getFlashdata('servicio_errores')['id_tipo_servicio'],
        );

        $this->db->table('propiedad_servicio')
            ->where('id_propiedad_servicio', $idServicio)
            ->update(['estado' => 'inactivo']);
        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post(
            '/inmuebles/' . $idPropiedad . '/servicios',
            $this->datosServicio($csrf, $idTipo),
        );
        $respuesta->assertRedirect();
        $this->assertSame(2, $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedad)
            ->countAllResults());
    }

    public function testOnlyActiveServicesAdmitInvoicesAndOnlyAllowedTransitionsAreAccepted(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.estados@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble estados', 'ocupado');
        $idServicio = $this->crearServicio($idPropiedad, $this->idTipoServicio('internet'), 'activo');
        $this->iniciarSesion($idPropietario, 'propietario');

        $service = new ServicioInmuebleService();
        $this->assertTrue($service->validarCargaFactura($idServicio, $idPropietario, 'propietario')['permitido']);

        $invalida = $this->postEstado($idServicio, 'en revision');
        $invalida->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->assertSame('Transición de estado inválida', session()->getFlashdata('error'));
        $this->assertSame('activo', $this->estadoServicio($idServicio));

        $this->postEstado($idServicio, 'pausado')->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $pausado = $service->validarCargaFactura($idServicio, $idPropietario, 'propietario');
        $this->assertFalse($pausado['permitido']);
        $this->assertSame('No se pueden cargar facturas sobre un servicio pausado', $pausado['mensaje']);

        $this->postEstado($idServicio, 'en revision')->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $revision = $service->validarCargaFactura($idServicio, $idPropietario, 'propietario');
        $this->assertFalse($revision['permitido']);
        $this->assertSame('Solo se pueden cargar facturas sobre un servicio activo', $revision['mensaje']);

        $this->postEstado($idServicio, 'activo')->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->assertTrue($service->validarCargaFactura($idServicio, $idPropietario, 'propietario')['permitido']);

        foreach ([
            ['agua', 'pausado'],
            ['gas', 'en revision'],
        ] as [$tipo, $estado]) {
            $idOtroServicio = $this->crearServicio(
                $idPropiedad,
                $this->idTipoServicio($tipo),
                $estado,
            );
            $detalle = $this->get('/servicios-inmueble/' . $idOtroServicio);
            $csrf = $this->csrfToken($detalle->response()->getBody());
            $this->withSession($_SESSION);
            $baja = $this->post('/servicios-inmueble/' . $idOtroServicio . '/baja', [
                'csrf_test_name' => $csrf,
            ]);

            $baja->assertRedirectTo('/servicios-inmueble/' . $idOtroServicio);
            $this->assertSame('inactivo', $this->estadoServicio($idOtroServicio));
        }
    }

    public function testInactivePropertyAndEmptyActiveCatalogPreventNewLinks(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.inactivo@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble inactivo', 'inactivo');
        $this->iniciarSesion($idPropietario, 'propietario');

        $respuesta = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $this->assertSame(
            'No se pueden agregar servicios a un inmueble inactivo',
            session()->getFlashdata('error'),
        );

        $detalle = $this->get('/inmuebles/' . $idPropiedad);
        $csrf = $this->csrfToken($detalle->response()->getBody());
        $this->withSession($_SESSION);
        $post = $this->post('/inmuebles/' . $idPropiedad . '/servicios', $this->datosServicio(
            $csrf,
            $this->idTipoServicio('agua'),
        ));
        $post->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $this->assertSame(
            'No se pueden agregar servicios a un inmueble inactivo',
            session()->getFlashdata('error'),
        );
        $this->assertSame(0, $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedad)
            ->countAllResults());

        $idPropiedadActiva = $this->crearPropiedad($idPropietario, 'Inmueble sin catálogo', 'disponible');
        $this->db->table('tipo_servicio')->update(['activo' => 0]);
        $catalogoVacio = $this->get('/inmuebles/' . $idPropiedadActiva . '/servicios/nuevo');
        $catalogoVacio->assertOK();
        $this->assertStringContainsString(
            'No hay tipos de servicio disponibles. Contacte al administrador.',
            $catalogoVacio->response()->getBody(),
        );
        $this->assertStringNotContainsString('name="id_tipo_servicio"', $catalogoVacio->response()->getBody());
    }

    public function testDeactivationPreservesExistingInvoiceAndPaymentHistory(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'servicio.historial@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble historial', 'disponible');
        $idServicio = $this->crearServicio($idPropiedad, $this->idTipoServicio('electricidad'), 'activo');
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '01',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-01-01',
            'fecha_primer_vencimiento' => '2026-01-15',
            'monto_total' => '1200.00',
            'consumo_del_periodo' => 10,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Proveedor histórico',
            'categoria_factura' => 'publico',
            'estado' => 'pagada',
        ]);
        $idFactura = (int) $this->db->insertID();
        $this->db->table('transaccion')->insert([
            'id_factura' => $idFactura,
            'id_propietario' => $idPropietario,
            'fecha_pago' => '2026-01-10',
            'monto_abonado' => '1200.00',
            'medio_pago' => 'transferencia',
            'numero_comprobante' => 'COMP-1',
            'en_mora' => 0,
        ]);
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/servicios-inmueble/' . $idServicio);
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);
        $respuesta = $this->post('/servicios-inmueble/' . $idServicio . '/baja', [
            'csrf_test_name' => $csrf,
        ]);

        $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->assertSame('inactivo', $this->estadoServicio($idServicio));
        $this->assertSame(1, $this->db->table('factura')->where('id_factura', $idFactura)->countAllResults());
        $this->assertSame(1, $this->db->table('transaccion')->where('id_factura', $idFactura)->countAllResults());
    }

    /**
     * @return array<string, string>
     */
    private function datosServicio(string $csrf, int $idTipo): array
    {
        return [
            'csrf_test_name' => $csrf,
            'id_tipo_servicio' => (string) $idTipo,
            'codigo_pago_electronico' => 'CPE-PRUEBA-1',
            'numero_cuenta_contrato' => 'CTA-PRUEBA-1',
            'numero_medidor' => '',
            'domicilio_facturacion' => 'Belgrano 100',
            'titular' => 'Titular de prueba',
        ];
    }

    private function postEstado(int $idServicio, string $estado): \CodeIgniter\Test\TestResponse
    {
        $detalle = $this->get('/servicios-inmueble/' . $idServicio);
        $csrf = $this->csrfToken($detalle->response()->getBody());
        $this->withSession($_SESSION);

        return $this->post('/servicios-inmueble/' . $idServicio . '/estado', [
            'csrf_test_name' => $csrf,
            'estado' => $estado,
        ]);
    }

    private function crearServicio(int $idPropiedad, int $idTipo, string $estado): int
    {
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE-BASE-1',
            'numero_cuenta_contrato' => 'CTA-BASE-1',
            'domicilio_facturacion' => 'Belgrano 100',
            'titular' => 'Titular base',
            'estado' => $estado,
        ]);

        return (int) $this->db->insertID();
    }

    private function estadoServicio(int $idServicio): string
    {
        return (string) $this->db->table('propiedad_servicio')
            ->select('estado')
            ->where('id_propiedad_servicio', $idServicio)
            ->get()
            ->getRow()
            ->estado;
    }

    private function crearUsuario(string $rol, string $mail): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario de prueba',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearPropiedad(int $idPropietario, string $alias, string $estado): int
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Belgrano',
            'numero' => 100,
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
            'alias' => $alias,
            'superficie' => '60.00',
            'estado' => $estado,
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);

        return (int) $this->db->insertID();
    }

    private function idTipoServicio(string $tipo): int
    {
        return (int) $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('tipo', $tipo)
            ->where('activo', 1)
            ->get()
            ->getRow()
            ->id_tipo_servicio;
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

    private function csrfToken(string $body): string
    {
        preg_match('/name="csrf_test_name"\s+value="([^"]+)"/', $body, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'No se encontró el token CSRF del formulario.');

        return html_entity_decode($matches[1], ENT_QUOTES);
    }
}
