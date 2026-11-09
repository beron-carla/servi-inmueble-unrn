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

    public function testOwnerCanLinkServiceAndItStartsActive(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idPropiedad = $this->crearPropiedad($idPropietario);
        $idTipo = $this->crearTipoServicio();
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $cuerpo = $formulario->response()->getBody();

        $formulario->assertOK();
        $this->assertStringContainsString('name="csrf_test_name"', $cuerpo);
        $this->assertStringContainsString('CPE/Pagar', $cuerpo);
        $this->assertStringContainsString('Número de cuenta', $cuerpo);
        $this->assertStringContainsString('Número de medidor (opcional)', $cuerpo);

        $respuesta = $this->post('/inmuebles/' . $idPropiedad . '/servicios', [
            'csrf_test_name' => $this->csrfToken($cuerpo),
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE-HU13',
            'numero_cuenta_contrato' => 'CTA-HU13',
            'numero_medidor' => '',
            'domicilio_facturacion' => 'Calle de prueba 123',
            'titular' => 'Titular de prueba',
        ]);

        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $servicio = $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedad)
            ->where('id_tipo_servicio', $idTipo)
            ->get()
            ->getRowArray();

        $this->assertNotNull($servicio);
        $this->assertSame('activo', $servicio['estado']);
        $this->assertNull($servicio['numero_medidor']);
    }

    public function testRequiredFieldsUseAcceptanceMessages(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idPropiedad = $this->crearPropiedad($idPropietario);
        $this->crearTipoServicio();
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $respuesta = $this->post('/inmuebles/' . $idPropiedad . '/servicios', [
            'csrf_test_name' => $this->csrfToken($formulario->response()->getBody()),
        ]);

        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $errores = session()->getFlashdata('servicio_errores');

        $this->assertSame('El tipo de servicio es obligatorio', $errores['id_tipo_servicio']);
        $this->assertSame('El CPE/Pagar es obligatorio', $errores['codigo_pago_electronico']);
        $this->assertSame('El número de cuenta es obligatorio', $errores['numero_cuenta_contrato']);
        $this->assertSame('El domicilio de facturación es obligatorio', $errores['domicilio_facturacion']);
        $this->assertSame('El titular es obligatorio', $errores['titular']);

        $this->assertSame(0, $this->db->table('propiedad_servicio')->where('id_propiedad', $idPropiedad)->countAllResults());
    }

    public function testOwnerGetsForbiddenForAnotherOwnersServiceButAdministratorCanOpenIt(): void
    {
        $idDuenio = $this->crearUsuario('propietario');
        $idAjeno = $this->crearUsuario('propietario');
        $idAdmin = $this->crearUsuario('admin');
        $idPropiedad = $this->crearPropiedad($idDuenio);
        $idTipo = $this->crearTipoServicio();
        $idServicio = $this->crearServicio($idPropiedad, $idTipo);

        $this->iniciarSesion($idAjeno, 'propietario');
        $respuesta = $this->get('/servicios-inmueble/' . $idServicio);
        $respuesta->assertStatus(403);
        $this->assertStringContainsString('Volver al dashboard', $respuesta->response()->getBody());

        $this->iniciarSesion($idAdmin, 'admin');
        $respuesta = $this->get('/servicios-inmueble/' . $idServicio);
        $respuesta->assertOK();
        $this->assertStringContainsString('CPE de prueba', $respuesta->response()->getBody());
    }

    public function testUniqueCurrentLinkCanBeRepeatedAfterLogicalDeactivation(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idPropiedad = $this->crearPropiedad($idPropietario);
        $idTipo = $this->crearTipoServicio();
        $idServicio = $this->crearServicio($idPropiedad, $idTipo);
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $respuesta = $this->post('/inmuebles/' . $idPropiedad . '/servicios', [
            'csrf_test_name' => $this->csrfToken($formulario->response()->getBody()),
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE-DUP',
            'numero_cuenta_contrato' => 'CTA-DUP',
            'domicilio_facturacion' => 'Domicilio de prueba',
            'titular' => 'Titular de prueba',
        ]);

        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $this->assertSame(
            'El inmueble ya tiene este servicio vinculado',
            session()->getFlashdata('error'),
        );

        $detalle = $this->get('/servicios-inmueble/' . $idServicio);
        $respuesta = $this->post('/servicios-inmueble/' . $idServicio . '/baja', [
            'csrf_test_name' => $this->csrfToken($detalle->response()->getBody()),
        ]);
        $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/servicios/nuevo');
        $respuesta = $this->post('/inmuebles/' . $idPropiedad . '/servicios', [
            'csrf_test_name' => $this->csrfToken($formulario->response()->getBody()),
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE-REVINCULO',
            'numero_cuenta_contrato' => 'CTA-REVINCULO',
            'domicilio_facturacion' => 'Domicilio de prueba',
            'titular' => 'Titular de prueba',
        ]);

        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $this->assertSame(
            2,
            $this->db->table('propiedad_servicio')
                ->where('id_propiedad', $idPropiedad)
                ->where('id_tipo_servicio', $idTipo)
                ->countAllResults(),
        );
    }

    public function testOnlyActiveServicesAllowInvoiceEntryAcrossPermittedTransitions(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idPropiedad = $this->crearPropiedad($idPropietario);
        $idTipo = $this->crearTipoServicio();
        $idServicio = $this->crearServicio($idPropiedad, $idTipo);
        $this->iniciarSesion($idPropietario, 'propietario');
        $servicios = new ServicioInmuebleService();

        $this->assertTrue($servicios->puedeCargarFacturas($idServicio, $idPropietario, 'propietario')['permitido']);

        foreach (['pausado', 'en revision', 'activo'] as $estado) {
            $detalle = $this->get('/servicios-inmueble/' . $idServicio);
            $respuesta = $this->post('/servicios-inmueble/' . $idServicio . '/estado', [
                'csrf_test_name' => $this->csrfToken($detalle->response()->getBody()),
                'estado' => $estado,
            ]);
            $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);

            $esperado = $estado === 'activo';
            $this->assertSame(
                $esperado,
                $servicios->puedeCargarFacturas($idServicio, $idPropietario, 'propietario')['permitido'],
            );
        }

        $detalle = $this->get('/servicios-inmueble/' . $idServicio);
        $respuesta = $this->post('/servicios-inmueble/' . $idServicio . '/baja', [
            'csrf_test_name' => $this->csrfToken($detalle->response()->getBody()),
        ]);
        $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->assertFalse($servicios->puedeCargarFacturas($idServicio, $idPropietario, 'propietario')['permitido']);

        $this->assertSame(
            'inactivo',
            $this->db->table('propiedad_servicio')
                ->where('id_propiedad_servicio', $idServicio)
                ->get()
                ->getRowArray()['estado'],
        );
    }

    public function testInactivePropertyAndEmptyActiveCatalogCannotAcceptNewServices(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idPropiedadInactiva = $this->crearPropiedad($idPropietario, 'inactivo');
        $this->crearTipoServicio();
        $this->iniciarSesion($idPropietario, 'propietario');

        $respuesta = $this->get('/inmuebles/' . $idPropiedadInactiva . '/servicios/nuevo');
        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedadInactiva);
        $this->assertSame(
            'No se pueden agregar servicios a un inmueble inactivo',
            session()->getFlashdata('error'),
        );

        $this->db->table('tipo_servicio')->update(['activo' => 0]);
        $idPropiedadActiva = $this->crearPropiedad($idPropietario);
        $respuesta = $this->get('/inmuebles/' . $idPropiedadActiva . '/servicios/nuevo');

        $respuesta->assertOK();
        $this->assertStringContainsString(
            'No hay tipos de servicio disponibles. Contacte al administrador.',
            $respuesta->response()->getBody(),
        );
        $this->assertStringNotContainsString('name="id_tipo_servicio"', $respuesta->response()->getBody());
    }

    public function testContractCanBeUpdatedWithoutChangingServiceState(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idPropiedad = $this->crearPropiedad($idPropietario);
        $idTipo = $this->crearTipoServicio();
        $idServicio = $this->crearServicio($idPropiedad, $idTipo);
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/servicios-inmueble/' . $idServicio . '/editar');
        $respuesta = $this->post('/servicios-inmueble/' . $idServicio, [
            'csrf_test_name' => $this->csrfToken($formulario->response()->getBody()),
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE-ACTUALIZADO',
            'numero_cuenta_contrato' => 'CTA-ACTUALIZADA',
            'numero_medidor' => 'MED-123',
            'domicilio_facturacion' => 'Nuevo domicilio',
            'titular' => 'Nuevo titular',
        ]);

        $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $servicio = $this->db->table('propiedad_servicio')
            ->where('id_propiedad_servicio', $idServicio)
            ->get()
            ->getRowArray();

        $this->assertSame('CPE-ACTUALIZADO', $servicio['codigo_pago_electronico']);
        $this->assertSame('Nuevo titular', $servicio['titular']);
        $this->assertSame('activo', $servicio['estado']);
    }

    private function crearUsuario(string $rol): int
    {
        $mail = 'hu13.' . bin2hex(random_bytes(5)) . '@example.test';
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario de prueba',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearPropiedad(int $idPropietario, string $estado = 'disponible'): int
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Calle de prueba',
            'numero' => 123,
            'ciudad' => 'Bariloche',
        ]);
        $idDireccion = (int) $this->db->insertID();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRowArray()['id_tipo_inmueble'];

        $this->db->table('propiedad')->insert([
            'alias' => 'Inmueble HU13 ' . bin2hex(random_bytes(4)),
            'superficie' => 50,
            'estado' => $estado,
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearTipoServicio(): int
    {
        $this->db->table('tipo_servicio')->insert([
            'tipo' => 'agua',
            'categoria' => 'publico',
            'unidad_medida' => 'm³',
            'proveedor_predeterminado' => 'Proveedor HU13 ' . bin2hex(random_bytes(4)),
            'activo' => 1,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearServicio(int $idPropiedad, int $idTipo): int
    {
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE de prueba',
            'numero_cuenta_contrato' => 'Cuenta de prueba',
            'domicilio_facturacion' => 'Domicilio de prueba',
            'titular' => 'Titular de prueba',
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
        $idSesion = (int) $this->db->insertID();

        $this->withSession([
            'id_usuario' => $idUsuario,
            'id_sesion' => $idSesion,
            'token' => $token,
            'rol' => $rol,
        ]);
    }

    private function csrfToken(string $body): string
    {
        preg_match(
            '/name="csrf_test_name"\s+value="([^"]+)"/',
            $body,
            $matches,
        );

        $this->assertNotEmpty($matches[1] ?? null, 'No se encontró el token CSRF del formulario.');

        $this->withSession($_SESSION);

        return html_entity_decode($matches[1], ENT_QUOTES);
    }
}
