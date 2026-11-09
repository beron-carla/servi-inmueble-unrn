<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AdminTiposServicioAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCannotOpenServiceTypeFormAndGetsForbiddenPage(): void
    {
        $idUsuario = $this->crearUsuario('propietario', 'propietario.tipos@example.test');
        $this->iniciarSesion($idUsuario, 'propietario');

        $respuesta = $this->get('/admin/tipos-servicio/nuevo');

        $respuesta->assertStatus(403);
        $this->assertStringContainsString('No tiene permisos para esta acción', $respuesta->response()->getBody());
        $this->assertStringContainsString('Volver al dashboard', $respuesta->response()->getBody());
    }

    public function testOwnerCannotOpenServiceTypeEditPage(): void
    {
        $idUsuario = $this->crearUsuario('propietario', 'propietario.tipos.editar@example.test');
        $idTipo = $this->crearTipoServicio('internet', 'privado', 'Gigas', 'Proveedor HU08 ajeno', 1);
        $this->iniciarSesion($idUsuario, 'propietario');

        $respuesta = $this->get('/admin/tipos-servicio/' . $idTipo . '/editar');

        $respuesta->assertStatus(403);
        $this->assertStringContainsString('No tiene permisos para esta acción', $respuesta->response()->getBody());
        $this->assertStringContainsString('Volver al dashboard', $respuesta->response()->getBody());
    }

    public function testAdministratorCanCreateAnActiveServiceTypeWithCsrf(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.tipos@example.test');
        $this->iniciarSesion($idAdmin, 'admin');

        $formulario = $this->get('/admin/tipos-servicio/nuevo');
        $formulario->assertOK();
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/admin/tipos-servicio', [
            'csrf_test_name' => $csrf,
            'tipo' => 'agua',
            'categoria' => 'publico',
            'unidad_medida' => 'm³',
            'proveedor_predeterminado' => 'Proveedor HU07',
        ]);

        $respuesta->assertRedirectTo('/admin/tipos-servicio/nuevo');
        $tipo = $this->db->table('tipo_servicio')
            ->where('tipo', 'agua')
            ->where('proveedor_predeterminado', 'Proveedor HU07')
            ->get()
            ->getRowArray();

        $this->assertNotNull($tipo);
        $this->assertSame('publico', $tipo['categoria']);
        $this->assertSame('m³', $tipo['unidad_medida']);
        $this->assertSame(1, (int) $tipo['activo']);
    }

    public function testCreateFormContainsCanonicalOptionsAndCsrfToken(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.formulario.tipos@example.test');
        $this->iniciarSesion($idAdmin, 'admin');

        $respuesta = $this->get('/admin/tipos-servicio/nuevo');
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('name="csrf_test_name"', $cuerpo);
        $this->assertStringContainsString('value="electricidad"', $cuerpo);
        $this->assertStringContainsString('value="publico"', $cuerpo);
        $this->assertStringContainsString('value="privado"', $cuerpo);
        $this->assertStringContainsString('value="kWh"', $cuerpo);
        $this->assertStringContainsString('value="m³"', $cuerpo);
        $this->assertStringContainsString('Proveedor predeterminado', $cuerpo);
    }

    public function testAdministratorCanListAllTypesAndSeeAcceptanceFields(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.listado.tipos@example.test');
        $this->iniciarSesion($idAdmin, 'admin');
        $this->crearTipoServicio('gas', 'privado', 'Moneda fija', 'Proveedor HU09 inactivo', 0);

        $respuesta = $this->get('/admin/tipos-servicio');
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('Nombre', $cuerpo);
        $this->assertStringContainsString('Categoría', $cuerpo);
        $this->assertStringContainsString('Unidad de medida', $cuerpo);
        $this->assertStringContainsString('Proveedor', $cuerpo);
        $this->assertStringContainsString('Proveedor HU09 inactivo', $cuerpo);
        $this->assertStringContainsString('/editar"', $cuerpo);
    }

    public function testAdministratorCanEditAndReactivateAServiceTypeWithCsrf(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.editar.tipos@example.test');
        $idTipo = $this->crearTipoServicio('internet', 'privado', 'Gigas', 'Proveedor HU08 editar', 1);
        $this->iniciarSesion($idAdmin, 'admin');

        $formulario = $this->get('/admin/tipos-servicio/' . $idTipo . '/editar');
        $cuerpo = $formulario->response()->getBody();
        $formulario->assertOK();
        $this->assertStringContainsString('name="activo"', $cuerpo);
        $this->assertStringContainsString('name="csrf_test_name"', $cuerpo);
        $csrf = $this->csrfToken($cuerpo);
        $this->withSession($_SESSION);

        $respuesta = $this->post('/admin/tipos-servicio/' . $idTipo, [
            'csrf_test_name' => $csrf,
            'tipo' => 'gas',
            'categoria' => 'publico',
            'unidad_medida' => 'Moneda fija',
            'proveedor_predeterminado' => 'Proveedor HU08 modificado',
            'activo' => '0',
        ]);

        $respuesta->assertRedirectTo('/admin/tipos-servicio');
        $tipo = $this->db->table('tipo_servicio')
            ->where('id_tipo_servicio', $idTipo)
            ->get()
            ->getRowArray();

        $this->assertSame('gas', $tipo['tipo']);
        $this->assertSame('publico', $tipo['categoria']);
        $this->assertSame('Moneda fija', $tipo['unidad_medida']);
        $this->assertSame('Proveedor HU08 modificado', $tipo['proveedor_predeterminado']);
        $this->assertSame(0, (int) $tipo['activo']);

        $formulario = $this->get('/admin/tipos-servicio/' . $idTipo . '/editar');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/admin/tipos-servicio/' . $idTipo . '/reactivar', [
            'csrf_test_name' => $csrf,
        ]);

        $respuesta->assertRedirectTo('/admin/tipos-servicio');
        $tipo = $this->db->table('tipo_servicio')
            ->where('id_tipo_servicio', $idTipo)
            ->get()
            ->getRowArray();
        $this->assertSame(1, (int) $tipo['activo']);

        $formulario = $this->get('/admin/tipos-servicio/' . $idTipo . '/editar');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/admin/tipos-servicio/' . $idTipo . '/desactivar', [
            'csrf_test_name' => $csrf,
        ]);

        $respuesta->assertRedirectTo('/admin/tipos-servicio');
        $tipo = $this->db->table('tipo_servicio')
            ->where('id_tipo_servicio', $idTipo)
            ->get()
            ->getRowArray();
        $this->assertSame(0, (int) $tipo['activo']);
    }

    public function testUpdateRejectsATypeAndProviderCombinationAlreadyInUse(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.duplicado.tipos@example.test');
        $idTipo = $this->crearTipoServicio('internet', 'privado', 'Gigas', 'Proveedor HU08 propio', 1);
        $this->crearTipoServicio('agua', 'publico', 'm³', 'Proveedor HU08 duplicado', 1);
        $this->iniciarSesion($idAdmin, 'admin');

        $formulario = $this->get('/admin/tipos-servicio/' . $idTipo . '/editar');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/admin/tipos-servicio/' . $idTipo, [
            'csrf_test_name' => $csrf,
            'tipo' => 'agua',
            'categoria' => 'publico',
            'unidad_medida' => 'm³',
            'proveedor_predeterminado' => 'proveedor hu08 duplicado',
            'activo' => '1',
        ]);

        $respuesta->assertRedirectTo('/admin/tipos-servicio/' . $idTipo . '/editar');
        $this->assertSame(
            'internet',
            $this->db->table('tipo_servicio')
                ->where('id_tipo_servicio', $idTipo)
                ->get()
                ->getRowArray()['tipo'],
        );
    }

    public function testPhysicalDeletionRouteClearlyReportsItsDeferredIntegration(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.baja.tipos@example.test');
        $idTipo = $this->crearTipoServicio('internet', 'privado', 'Gigas', 'Proveedor HU08 baja', 1);
        $this->iniciarSesion($idAdmin, 'admin');

        $formulario = $this->get('/admin/tipos-servicio/' . $idTipo . '/editar');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);

        $respuesta = $this->post('/admin/tipos-servicio/' . $idTipo . '/eliminar', [
            'csrf_test_name' => $csrf,
        ]);

        $respuesta->assertRedirectTo('/admin/tipos-servicio/' . $idTipo);
        $this->assertSame(
            'La eliminación se habilitará al completar la integración con HU-13 y HU-14.',
            session()->getFlashdata('warning'),
        );
        $this->assertSame(
            1,
            $this->db->table('tipo_servicio')->where('id_tipo_servicio', $idTipo)->countAllResults(),
        );
    }

    public function testAdministratorFiltersTypesByCategoryAndActiveState(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.filtros.tipos@example.test');
        $this->iniciarSesion($idAdmin, 'admin');
        $this->crearTipoServicio('agua', 'privado', 'm³', 'Proveedor HU09 filtrado', 0);
        $this->crearTipoServicio('gas', 'publico', 'Moneda fija', 'Proveedor HU09 excluido', 1);

        $respuesta = $this->get('/admin/tipos-servicio?categoria=privado&activo=0');
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('Proveedor HU09 filtrado', $cuerpo);
        $this->assertStringNotContainsString('Proveedor HU09 excluido', $cuerpo);
    }

    public function testOwnerSeesOnlyActiveTypesInTheReadOnlyCatalog(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.catalogo@example.test');
        $this->iniciarSesion($idPropietario, 'propietario');
        $this->crearTipoServicio('otro', 'privado', 'Gigas', 'Proveedor HU09 visible', 1);
        $this->crearTipoServicio('otro', 'publico', 'Gigas', 'Proveedor HU09 oculto', 0);

        $respuesta = $this->get('/catalogo-servicios');
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('Proveedor HU09 visible', $cuerpo);
        $this->assertStringNotContainsString('Proveedor HU09 oculto', $cuerpo);
        $this->assertStringNotContainsString('Acciones', $cuerpo);
        $this->assertStringNotContainsString('Editar', $cuerpo);
    }

    public function testAdministratorCanViewTypeDetailsAndDeferredPropertyCountIsIdentified(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.detalle.tipos@example.test');
        $this->iniciarSesion($idAdmin, 'admin');
        $idTipo = $this->crearTipoServicio('internet', 'privado', 'Gigas', 'Proveedor HU09 detalle', 1);

        $respuesta = $this->get('/admin/tipos-servicio/' . $idTipo);
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('Privado', $cuerpo);
        $this->assertStringContainsString('Gigas', $cuerpo);
        $this->assertStringContainsString('Proveedor HU09 detalle', $cuerpo);
        $this->assertStringContainsString('integración con HU-13', $cuerpo);
    }

    public function testCatalogDisplaysRequiredEmptyStateWhenSearchHasNoResults(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'propietario.catalogo.vacio@example.test');
        $this->iniciarSesion($idPropietario, 'propietario');

        $respuesta = $this->get('/catalogo-servicios?q=sin-coincidencias-HU09');

        $respuesta->assertOK();
        $this->assertStringContainsString(
            'No hay tipos de servicio registrados',
            $respuesta->response()->getBody(),
        );
    }

    public function testAdminPaginationUsesDocumentedPageParameterAndPreservesFilters(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.paginacion.tipos@example.test');
        $this->iniciarSesion($idAdmin, 'admin');

        for ($indice = 0; $indice < 21; $indice++) {
            $this->crearTipoServicio(
                'otro',
                'publico',
                'Gigas',
                sprintf('Proveedor HU09 paginacion %02d', $indice),
                1,
            );
        }

        $respuesta = $this->get(
            '/admin/tipos-servicio?q=Proveedor+HU09+paginacion&categoria=publico&page=2',
        );
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('Proveedor HU09 paginacion 20', $cuerpo);
        $this->assertStringNotContainsString('Proveedor HU09 paginacion 00', $cuerpo);
        $this->assertStringContainsString('categoria=publico', $cuerpo);
        $this->assertStringContainsString('page=1', $cuerpo);
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

    private function crearTipoServicio(
        string $tipo,
        string $categoria,
        string $unidad,
        string $proveedor,
        int $activo,
    ): int {
        $this->db->table('tipo_servicio')->insert([
            'tipo' => $tipo,
            'categoria' => $categoria,
            'unidad_medida' => $unidad,
            'proveedor_predeterminado' => $proveedor,
            'activo' => $activo,
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

        return html_entity_decode($matches[1], ENT_QUOTES);
    }
}
