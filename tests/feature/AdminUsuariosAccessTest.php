<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AdminUsuariosAccessTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCannotOpenUserAdministrationAndGetsForbiddenPage(): void
    {
        $idUsuario = $this->crearUsuario('propietario', 'propietario.ruta@example.test');
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
            'rol' => 'propietario',
        ]);

        $respuesta = $this->get('/admin/usuarios/' . $idUsuario . '/editar');

        $respuesta->assertStatus(403);
        $this->assertStringContainsString('No tiene permisos para esta acción', $respuesta->response()->getBody());
        $this->assertStringContainsString('Volver al dashboard', $respuesta->response()->getBody());
    }

    public function testAdministratorCanOpenEditFormWithCsrfAndStateOptions(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.edicion@example.test');
        $idUsuario = $this->crearUsuario('propietario', 'propietario.edicion@example.test');
        $token = bin2hex(random_bytes(32));
        $this->db->table('sesion')->insert([
            'id_usuario' => $idAdmin,
            'token' => $token,
        ]);
        $idSesion = (int) $this->db->insertID();
        $this->withSession([
            'id_usuario' => $idAdmin,
            'id_sesion' => $idSesion,
            'token' => $token,
            'rol' => 'admin',
        ]);

        $respuesta = $this->get('/admin/usuarios/' . $idUsuario . '/editar');

        $respuesta->assertOK();
        $cuerpo = $respuesta->response()->getBody();
        $this->assertStringContainsString('Modificar datos del usuario', $cuerpo);
        $this->assertStringContainsString('name="csrf_test_name"', $cuerpo);
        $this->assertStringContainsString('value="bloqueado"', $cuerpo);
        $this->assertStringContainsString('value="baja"', $cuerpo);
    }

    public function testAdministratorCanUpdateAUserThroughThePostRoute(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.actualizar@example.test');
        $idUsuario = $this->crearUsuario('propietario', 'propietario.actualizar@example.test');
        $token = bin2hex(random_bytes(32));
        $this->db->table('sesion')->insert([
            'id_usuario' => $idAdmin,
            'token' => $token,
        ]);
        $idSesion = (int) $this->db->insertID();
        $this->withSession([
            'id_usuario' => $idAdmin,
            'id_sesion' => $idSesion,
            'token' => $token,
            'rol' => 'admin',
        ]);

        $formulario = $this->get('/admin/usuarios/' . $idUsuario . '/editar');
        $csrf = $this->csrfToken($formulario->response()->getBody());
        $this->withSession($_SESSION);
        $respuesta = $this->post('/admin/usuarios/' . $idUsuario, [
            'csrf_test_name' => $csrf,
            'nombre_completo' => 'Nombre actualizado',
            'mail' => ' ACTUALIZADO@EXAMPLE.TEST ',
            'rol' => 'propietario',
            'estado' => 'activo',
        ]);

        $respuesta->assertRedirectTo('/admin/usuarios/' . $idUsuario);
        $usuario = $this->db->table('usuario')->where('id_usuario', $idUsuario)->get()->getRow();
        $this->assertSame('Nombre actualizado', $usuario->nombre_completo);
        $this->assertSame('actualizado@example.test', $usuario->mail);
        $this->assertSame(1, $this->db->table('modificacion_usuario')
            ->where('id_usuario', $idUsuario)
            ->countAllResults());
    }

    public function testAdministratorResettingOwnPasswordCanSeeTemporaryPasswordOnce(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.reset-propio@example.test');
        $token = bin2hex(random_bytes(32));
        $this->db->table('sesion')->insert([
            'id_usuario' => $idAdmin,
            'token' => $token,
        ]);
        $idSesion = (int) $this->db->insertID();
        $this->withSession([
            'id_usuario' => $idAdmin,
            'id_sesion' => $idSesion,
            'token' => $token,
            'rol' => 'admin',
        ]);

        $detalle = $this->get('/admin/usuarios/' . $idAdmin);
        $csrf = $this->csrfToken($detalle->response()->getBody());
        $this->withSession($_SESSION);
        $respuesta = $this->post('/admin/usuarios/' . $idAdmin . '/restablecer-contrasenia', [
            'csrf_test_name' => $csrf,
        ]);

        $respuesta->assertRedirectTo('/auth/login');
        $this->withSession($_SESSION);
        $login = $this->get('/auth/login');
        $cuerpo = $login->response()->getBody();
        $this->assertStringContainsString('Contraseña temporal:', $cuerpo);
        $this->assertSame(0, $this->db->table('sesion')->where('id_usuario', $idAdmin)->countAllResults());
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
