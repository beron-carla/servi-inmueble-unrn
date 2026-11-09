<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use App\Services\InmuebleService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class InmueblesTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCanCreatePropertyAndAddressForTheirOwnAccount(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.alta@example.test');
        $this->iniciarSesion($idPropietario, 'propietario');
        $formulario = $this->get('/inmuebles/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles', [
            'csrf_test_name' => $csrf,
            'alias' => 'Casa Norte',
            'calle' => 'San Martín',
            'numero' => '00123',
            'piso' => '',
            'depto' => '',
            'ciudad' => 'Roca',
            'id_tipo_inmueble' => $this->idTipo('casa'),
            'superficie' => '80.50',
            'estado' => 'disponible',
            'id_propietario' => $this->crearUsuario('admin', 'admin.ajeno@example.test'),
        ]);
        $respuesta->assertRedirectTo('/inmuebles');

        $propiedad = $this->db->table('propiedad')
            ->where('id_propietario', $idPropietario)
            ->where('alias', 'Casa Norte')
            ->get()
            ->getRowArray();
        $direccion = $this->db->table('direccion')
            ->where('id_direccion', $propiedad['id_direccion'])
            ->get()
            ->getRowArray();

        $this->assertSame('80.50', $propiedad['superficie']);
        $this->assertSame('disponible', $propiedad['estado']);
        $this->assertSame('San Martín', $direccion['calle']);
        $this->assertSame(123, (int) $direccion['numero']);
        $this->assertNull($direccion['piso']);
        $this->assertNull($direccion['depto']);
        $this->assertSame('Roca', $direccion['ciudad']);

        $idTipoServicio = (int) $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('tipo', 'electricidad')
            ->get()
            ->getRow()
            ->id_tipo_servicio;
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $propiedad['id_propiedad'],
            'id_tipo_servicio' => $idTipoServicio,
            'codigo_pago_electronico' => 'COD-ALT-10',
            'numero_cuenta_contrato' => 'CTA-ALT-10',
            'domicilio_facturacion' => 'San Martín 123',
            'titular' => 'Propietario de prueba',
            'estado' => 'activo',
        ]);
        $detalle = $this->get('/inmuebles/' . $propiedad['id_propiedad']);
        $detalle->assertOK();
        $this->assertStringContainsString('Servicios vinculados', $detalle->response()->getBody());
        $this->assertStringContainsString('Electricidad', $detalle->response()->getBody());
        $this->assertStringContainsString('HU-14 y HU-15', $detalle->response()->getBody());
    }

    public function testAdministratorCanCreatePropertyForAnActiveOwnerOnly(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.alta.inmueble@example.test');
        $idPropietario = $this->crearUsuario('propietario', 'activo.alta.inmueble@example.test');
        $idBloqueado = $this->crearUsuario('propietario', 'bloqueado.alta.inmueble@example.test', 'bloqueado');
        $this->iniciarSesion($idAdmin, 'admin');

        $formulario = $this->get('/inmuebles/nuevo');
        $cuerpo = $formulario->response()->getBody();
        $this->assertStringContainsString('activo.alta.inmueble@example.test', $cuerpo);
        $this->assertStringNotContainsString('bloqueado.alta.inmueble@example.test', $cuerpo);
        $csrf = $this->csrfToken($cuerpo);
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles', [
            'csrf_test_name' => $csrf,
            'alias' => 'Local Admin',
            'calle' => 'Mitre',
            'numero' => '20',
            'ciudad' => 'Cipolletti',
            'id_tipo_inmueble' => $this->idTipo('local comercial'),
            'superficie' => '45',
            'estado' => 'ocupado',
            'id_propietario' => $idPropietario,
        ]);
        $respuesta->assertRedirectTo('/inmuebles');

        $propiedad = $this->db->table('propiedad')
            ->where('alias', 'Local Admin')
            ->get()
            ->getRowArray();
        $this->assertSame($idPropietario, (int) $propiedad['id_propietario']);
    }

    public function testDuplicateAliasIsRejectedByTheApplicationAndPreservesFormInput(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.alias.duplicado@example.test');
        $this->crearPropiedad($idPropietario, 'Casa Repetida', 'ocupado');
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles', [
            'csrf_test_name' => $csrf,
            'alias' => 'Casa Repetida',
            'calle' => 'Otra calle',
            'numero' => '9',
            'ciudad' => 'Roca',
            'id_tipo_inmueble' => $this->idTipo('casa'),
            'superficie' => '80',
            'estado' => 'disponible',
        ]);
        $respuesta->assertRedirectTo('/inmuebles/nuevo');
        $this->assertSame(
            'El alias ya está registrado para este propietario.',
            session()->getFlashdata('inmueble_errores')['alias'],
        );
        $this->assertSame('Casa Repetida', session()->getFlashdata('inmueble_anterior')['alias']);
        $this->assertSame(1, $this->db->table('propiedad')
            ->where('id_propietario', $idPropietario)
            ->where('alias', 'Casa Repetida')
            ->countAllResults());
    }

    public function testInvalidValuesShowTheAcceptanceCriteriaMessages(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.validacion@example.test');
        $this->iniciarSesion($idPropietario, 'propietario');
        $formulario = $this->get('/inmuebles/nuevo');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles', [
            'csrf_test_name' => $csrf,
            'alias' => '',
            'calle' => '',
            'numero' => 'uno',
            'ciudad' => '',
            'id_tipo_inmueble' => '999999',
            'superficie' => '0.001',
            'estado' => 'Alquilado',
        ]);
        $respuesta->assertRedirectTo('/inmuebles/nuevo');
        $errores = session()->getFlashdata('inmueble_errores');

        $mensajesEsperados = [
            'El alias es obligatorio',
            'La calle es obligatoria',
            'El número ingresado es invalido. Por favor vuelva a intentarlo.',
            'La ciudad es obligatoria',
            'Tipo de inmueble inválido',
            'La superficie admite hasta 2 decimales',
            'Estado inválido',
        ];
        foreach (array_values($errores) as $indice => $mensaje) {
            $this->assertContains($mensaje, $mensajesEsperados);
        }
        $this->assertCount(count($mensajesEsperados), $errores);

        $this->assertSame(0, $this->db->table('propiedad')
            ->where('id_propietario', $idPropietario)
            ->countAllResults());
    }

    public function testSurfaceMustBeNumericPositiveAndAllowNoMoreThanTwoDecimals(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.superficie@example.test');
        $entrada = [
            'alias' => 'Superficie válida',
            'calle' => 'Mitre',
            'numero' => '20',
            'ciudad' => 'Roca',
            'id_tipo_inmueble' => (string) $this->idTipo('casa'),
            'superficie' => 'abc',
            'estado' => 'ocupado',
        ];
        $servicio = new InmuebleService();

        $noNumerica = $servicio->crear($entrada, $idPropietario, 'propietario');
        $this->assertSame('La superficie debe ser numérica', $noNumerica['errores']['superficie']);

        $entrada['superficie'] = '0';
        $noPositiva = $servicio->crear($entrada, $idPropietario, 'propietario');
        $this->assertSame('La superficie debe ser > 0', $noPositiva['errores']['superficie']);

        $entrada['superficie'] = '1.234';
        $demasiadosDecimales = $servicio->crear($entrada, $idPropietario, 'propietario');
        $this->assertSame(
            'La superficie admite hasta 2 decimales',
            $demasiadosDecimales['errores']['superficie'],
        );
    }

    public function testOwnerListingIsScopedAndHidesInactivePropertiesByDefault(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.listado.inmuebles@example.test');
        $otroPropietario = $this->crearUsuario('propietario', 'otro.listado.inmuebles@example.test');
        $this->crearPropiedad($idPropietario, 'Casa Visible', 'disponible', 'Roca');
        $this->crearPropiedad($idPropietario, 'Casa Inactiva', 'inactivo', 'Roca');
        $this->crearPropiedad($otroPropietario, 'Casa Ajena', 'disponible', 'Cipolletti');
        $this->iniciarSesion($idPropietario, 'propietario');

        $listado = $this->get('/inmuebles');
        $cuerpo = $listado->response()->getBody();
        $listado->assertOK();
        $this->assertStringContainsString('Casa Visible', $cuerpo);
        $this->assertStringNotContainsString('Casa Inactiva', $cuerpo);
        $this->assertStringNotContainsString('Casa Ajena', $cuerpo);

        $inactivos = $this->get('/inmuebles?estado=inactivo');
        $inactivos->assertOK();
        $this->assertStringContainsString('Casa Inactiva', $inactivos->response()->getBody());
        $this->assertStringNotContainsString('Casa Ajena', $inactivos->response()->getBody());

        $this->crearPropiedad($idPropietario, 'Depto Filtrado', 'disponible', 'Roca', 'departamento');
        $filtrados = $this->get('/inmuebles?ciudad=Roca&tipo=departamento');
        $filtrados->assertOK();
        $this->assertStringContainsString('Depto Filtrado', $filtrados->response()->getBody());
        $this->assertStringNotContainsString('Casa Visible', $filtrados->response()->getBody());
    }

    public function testAdministratorCanFilterAllPropertiesAndOwnersReceiveForbiddenOnForeignDetail(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.listado.inmuebles@example.test');
        $idPropietario = $this->crearUsuario('propietario', 'propietario.admin.listado@example.test');
        $idOtroPropietario = $this->crearUsuario('propietario', 'otro.admin.listado@example.test');
        $idVisible = $this->crearPropiedad($idPropietario, 'Depto Administrado', 'disponible', 'Roca');
        $this->crearPropiedad($idOtroPropietario, 'Casa Otra Ciudad', 'disponible', 'Cipolletti');

        $this->iniciarSesion($idAdmin, 'admin');
        $adminListado = $this->get('/admin/inmuebles?propietario=' . $idPropietario . '&ciudad=Roca');
        $cuerpoAdmin = $adminListado->response()->getBody();
        $adminListado->assertOK();
        $this->assertStringContainsString('Depto Administrado', $cuerpoAdmin);
        $this->assertStringNotContainsString('Casa Otra Ciudad', $cuerpoAdmin);

        $this->iniciarSesion($idOtroPropietario, 'propietario');
        $detalleAjeno = $this->get('/inmuebles/' . $idVisible);
        $detalleAjeno->assertStatus(403);
        $this->assertStringContainsString('No tiene permisos para esta acción', $detalleAjeno->response()->getBody());
        $this->assertStringContainsString('Volver al dashboard', $detalleAjeno->response()->getBody());
    }

    public function testOwnerCanUpdatePropertyAddressTypeSurfaceAndState(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.editar.inmueble@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Casa Original', 'ocupado');
        $idDireccionOriginal = (int) $this->db->table('propiedad')
            ->select('id_direccion')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRow()
            ->id_direccion;
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/editar');
        $formulario->assertOK();
        $this->assertStringContainsString('Casa Original', $formulario->response()->getBody());
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles/' . $idPropiedad, [
            'csrf_test_name' => $csrf,
            'alias' => 'Casa Actualizada',
            'calle' => 'San Juan',
            'numero' => '75',
            'piso' => '2',
            'depto' => 'B',
            'ciudad' => 'Cipolletti',
            'id_tipo_inmueble' => (string) $this->idTipo('departamento'),
            'superficie' => '92.75',
            'estado' => 'en mantenimiento',
        ]);
        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad);

        $propiedad = $this->db->table('propiedad')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRowArray();
        $direccion = $this->db->table('direccion')
            ->where('id_direccion', $idDireccionOriginal)
            ->get()
            ->getRowArray();

        $this->assertSame('Casa Actualizada', $propiedad['alias']);
        $this->assertSame('92.75', $propiedad['superficie']);
        $this->assertSame('en mantenimiento', $propiedad['estado']);
        $this->assertSame($this->idTipo('departamento'), (int) $propiedad['id_tipo_inmueble']);
        $this->assertSame($idDireccionOriginal, (int) $propiedad['id_direccion']);
        $this->assertSame('San Juan', $direccion['calle']);
        $this->assertSame(75, (int) $direccion['numero']);
        $this->assertSame(2, (int) $direccion['piso']);
        $this->assertSame('B', $direccion['depto']);
        $this->assertSame('Cipolletti', $direccion['ciudad']);
        $this->assertSame(1, $this->db->table('direccion')
            ->where('id_direccion', $idDireccionOriginal)
            ->countAllResults());
    }

    public function testPropertyUpdateUsesAcceptanceCriteriaValidationMessages(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.validar.edicion@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Casa Validada', 'ocupado');
        $this->iniciarSesion($idPropietario, 'propietario');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/editar');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/inmuebles/' . $idPropiedad, [
            'csrf_test_name' => $csrf,
            'alias' => '',
            'calle' => 'Mitre',
            'numero' => '20',
            'ciudad' => 'Roca',
            'id_tipo_inmueble' => (string) $this->idTipo('casa'),
            'superficie' => '-5',
            'estado' => 'Alquilado',
        ]);
        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad . '/editar');

        $this->assertSame([
            'alias' => 'El alias es obligatorio',
            'estado' => 'Estado inválido',
            'superficie' => 'La superficie debe ser > 0',
        ], session()->getFlashdata('inmueble_errores'));
        $this->assertSame('Casa Validada', $this->db->table('propiedad')
            ->select('alias')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRow()
            ->alias);
    }

    public function testLogicalDeactivationPreservesPropertyServicesAndInvoicesAndReactivationSetsAvailable(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.baja.inmueble@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Casa con historial', 'disponible');
        $idFactura = $this->crearServicioConFactura($idPropiedad);
        $this->iniciarSesion($idPropietario, 'propietario');

        $detalle = $this->get('/inmuebles/' . $idPropiedad);
        $csrf = $this->csrfToken($detalle->response()->getBody());
        $this->withSession($_SESSION);

        $baja = $this->post('/inmuebles/' . $idPropiedad . '/baja', ['csrf_test_name' => $csrf]);
        $baja->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $this->assertSame('inactivo', $this->estadoPropiedad($idPropiedad));
        $this->assertSame(1, $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $idPropiedad)
            ->countAllResults());
        $this->assertSame(1, $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->countAllResults());
        $lecturas = $this->db->table('factura')
            ->select('lectura_anterior, lectura_actual')
            ->where('id_factura', $idFactura)
            ->get()
            ->getRowArray();
        $this->assertSame(100, (int) $lecturas['lectura_anterior']);
        $this->assertSame(110, (int) $lecturas['lectura_actual']);

        $detalleInactivo = $this->get('/inmuebles/' . $idPropiedad);
        $this->assertStringContainsString('Reactivar', $detalleInactivo->response()->getBody());
        $csrfReactivar = $this->csrfToken($detalleInactivo->response()->getBody());
        $this->withSession($_SESSION);
        $reactivacion = $this->post('/inmuebles/' . $idPropiedad . '/reactivar', [
            'csrf_test_name' => $csrfReactivar,
        ]);

        $reactivacion->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $this->assertSame('disponible', $this->estadoPropiedad($idPropiedad));
        $this->assertSame(1, $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->countAllResults());
        $listado = $this->get('/inmuebles');
        $this->assertStringContainsString('Casa con historial', $listado->response()->getBody());
    }

    public function testOwnerCannotEditOrChangeStateOfAnotherOwnersProperty(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.duena.inmueble@example.test');
        $idOtroPropietario = $this->crearUsuario('propietario', 'propietario.ajeno.inmueble@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Casa Protegida', 'inactivo');
        $this->crearPropiedad($idOtroPropietario, 'Casa Propia', 'disponible');
        $this->iniciarSesion($idOtroPropietario, 'propietario');

        $edicionAjena = $this->get('/inmuebles/' . $idPropiedad . '/editar');
        $edicionAjena->assertStatus(403);
        $this->assertStringContainsString('Volver al dashboard', $edicionAjena->response()->getBody());

        $idPropiedadPropia = $this->idPropiedadPorAlias('Casa Propia');
        $csrf = $this->csrfDesdeDetalle($idPropiedadPropia);
        $actualizacionAjena = $this->post('/inmuebles/' . $idPropiedad, [
            'csrf_test_name' => $csrf,
            'alias' => 'Casa Modificada',
            'calle' => 'Mitre',
            'numero' => '10',
            'ciudad' => 'Roca',
            'id_tipo_inmueble' => (string) $this->idTipo('casa'),
            'superficie' => '60',
            'estado' => 'disponible',
        ]);
        $actualizacionAjena->assertStatus(403);

        $csrf = $this->csrfDesdeDetalle($idPropiedadPropia);
        $bajaAjena = $this->post('/inmuebles/' . $idPropiedad . '/baja', ['csrf_test_name' => $csrf]);
        $bajaAjena->assertStatus(403);

        $csrf = $this->csrfDesdeDetalle($idPropiedadPropia);
        $reactivacionAjena = $this->post('/inmuebles/' . $idPropiedad . '/reactivar', [
            'csrf_test_name' => $csrf,
        ]);
        $reactivacionAjena->assertStatus(403);
        $this->assertSame('inactivo', $this->estadoPropiedad($idPropiedad));
    }

    public function testAdministratorCanEditAPropertyOwnedByAnotherUser(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.editar.inmueble@example.test');
        $idPropietario = $this->crearUsuario('propietario', 'propietario.admin.editar@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Casa Administrada', 'disponible');
        $this->iniciarSesion($idAdmin, 'admin');

        $formulario = $this->get('/inmuebles/' . $idPropiedad . '/editar');
        $formulario->assertOK();
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);
        $respuesta = $this->post('/inmuebles/' . $idPropiedad, [
            'csrf_test_name' => $csrf,
            'alias' => 'Casa Actualizada por Admin',
            'calle' => 'Mitre',
            'numero' => '12',
            'ciudad' => 'Roca',
            'id_tipo_inmueble' => (string) $this->idTipo('casa'),
            'superficie' => '60',
            'estado' => 'disponible',
        ]);

        $respuesta->assertRedirectTo('/inmuebles/' . $idPropiedad);
        $this->assertSame('Casa Actualizada por Admin', $this->db->table('propiedad')
            ->select('alias')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRow()
            ->alias);
    }

    private function crearServicioConFactura(int $idPropiedad): int
    {
        $idTipoServicio = (int) $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('tipo', 'electricidad')
            ->get()
            ->getRow()
            ->id_tipo_servicio;
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => $idTipoServicio,
            'codigo_pago_electronico' => 'COD-HU11',
            'numero_cuenta_contrato' => 'CTA-HU11',
            'domicilio_facturacion' => 'Belgrano 100',
            'titular' => 'Titular de prueba',
            'estado' => 'activo',
        ]);
        $idPropiedadServicio = (int) $this->db->insertID();
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idPropiedadServicio,
            'periodo' => 'mes',
            'periodo_mes' => '01',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-01-01',
            'fecha_primer_vencimiento' => '2026-01-10',
            'monto_total' => '100.00',
            'consumo_del_periodo' => 10,
            'lectura_anterior' => 100,
            'lectura_actual' => 110,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Proveedor de prueba',
            'categoria_factura' => 'publico',
            'estado' => 'pendiente',
        ]);

        return (int) $this->db->insertID();
    }

    private function idPropiedadPorAlias(string $alias): int
    {
        return (int) $this->db->table('propiedad')
            ->select('id_propiedad')
            ->where('alias', $alias)
            ->get()
            ->getRow()
            ->id_propiedad;
    }

    private function estadoPropiedad(int $idPropiedad): string
    {
        return (string) $this->db->table('propiedad')
            ->select('estado')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRow()
            ->estado;
    }

    private function csrfDesdeDetalle(int $idPropiedad): string
    {
        $detalle = $this->get('/inmuebles/' . $idPropiedad);
        $token = $this->csrfToken($detalle->response()->getBody());
        $this->withSession($_SESSION);

        return $token;
    }

    private function crearUsuario(string $rol, string $mail, string $estado = 'activo'): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario de prueba',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => $estado,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearPropiedad(
        int $idPropietario,
        string $alias,
        string $estado,
        string $ciudad = 'Roca',
        string $tipo = 'casa',
    ): int {
        $this->db->table('direccion')->insert([
            'calle' => 'Belgrano',
            'numero' => 100,
            'ciudad' => $ciudad,
        ]);
        $idDireccion = (int) $this->db->insertID();

        $this->db->table('propiedad')->insert([
            'alias' => $alias,
            'superficie' => '60.00',
            'estado' => $estado,
            'id_tipo_inmueble' => $this->idTipo($tipo),
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);

        return (int) $this->db->insertID();
    }

    private function idTipo(string $nombre): int
    {
        return (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', $nombre)
            ->get()
            ->getRow()
            ->id_tipo_inmueble;
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
