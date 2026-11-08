<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\UsuarioModel;
use App\Repositories\UsuarioRepository;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UsuarioListadoTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testSearchMatchesPartialNameAndEmailWithoutCaseSensitivity(): void
    {
        $this->crearUsuario('Juan Pérez', 'juan.consulta@example.test', 'propietario', 'activo');

        $repositorio = new UsuarioRepository(new UsuarioModel($this->db));
        $porNombre = $repositorio->listar(['q' => 'JUAN'], 1)['usuarios'];
        $porMail = $repositorio->listar(['q' => 'CONSULTA@EXAMPLE'], 1)['usuarios'];

        $this->assertCount(1, $porNombre);
        $this->assertSame('juan.consulta@example.test', $porNombre[0]['mail']);
        $this->assertCount(1, $porMail);
        $this->assertSame('juan.consulta@example.test', $porMail[0]['mail']);
    }

    public function testRoleAndStateFiltersCanBeCombinedAndEmptySearchReturnsNoUsers(): void
    {
        $this->crearUsuario('María Baja', 'maria.baja@example.test', 'propietario', 'baja');
        $repositorio = new UsuarioRepository(new UsuarioModel($this->db));

        $filtrados = $repositorio->listar([
            'rol' => 'propietario',
            'estado' => 'baja',
        ], 1)['usuarios'];
        $sinResultados = $repositorio->listar(['q' => 'texto-inexistente-06'], 1)['usuarios'];

        $this->assertCount(1, $filtrados);
        $this->assertSame('maria.baja@example.test', $filtrados[0]['mail']);
        $this->assertSame([], $sinResultados);
    }

    private function crearUsuario(string $nombre, string $mail, string $rol, string $estado): void
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => $nombre,
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => $estado,
        ]);
    }
}
