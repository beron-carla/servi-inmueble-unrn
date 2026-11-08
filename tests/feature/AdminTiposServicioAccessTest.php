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
