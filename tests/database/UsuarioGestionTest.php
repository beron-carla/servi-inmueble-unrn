<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\UsuarioService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UsuarioGestionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testUpdateNormalizesEmailAndAuditsTheAdmin(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.gestion@example.test');
        $idUsuario = $this->crearUsuario('propietario', 'usuario.gestion@example.test');

        $resultado = (new UsuarioService())->actualizar(
            $idUsuario,
            $idAdmin,
            '  María Pérez  ',
            ' MARIA.PEREZ@EXAMPLE.TEST ',
            'admin',
            'activo',
        );

        $usuario = $this->db->table('usuario')->where('id_usuario', $idUsuario)->get()->getRowArray();
        $auditoria = $this->db->table('modificacion_usuario')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getRowArray();

        $this->assertTrue($resultado['exito']);
        $this->assertSame('María Pérez', $usuario['nombre_completo']);
        $this->assertSame('maria.perez@example.test', $usuario['mail']);
        $this->assertSame('admin', $usuario['rol']);
        $this->assertSame($idAdmin, (int) $auditoria['id_admin']);
        $this->assertNotEmpty($auditoria['fecha_modificacion']);
    }

    public function testDuplicateEmailIncludesUsersInBaja(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.duplicado@example.test');
        $idUsuario = $this->crearUsuario('propietario', 'usuario.duplicado@example.test');
        $this->crearUsuario('propietario', 'baja.duplicado@example.test', 'baja');

        $resultado = (new UsuarioService())->actualizar(
            $idUsuario,
            $idAdmin,
            'Usuario',
            ' BAJA.DUPLICADO@EXAMPLE.TEST ',
            'propietario',
            'activo',
        );

        $this->assertFalse($resultado['exito']);
        $this->assertSame('El email ya se encuentra registrado', $resultado['errores']['mail']);
        $this->assertSame(
            'usuario.duplicado@example.test',
            $this->db->table('usuario')->where('id_usuario', $idUsuario)->get()->getRow()->mail,
        );
        $this->assertSame(0, $this->db->table('modificacion_usuario')
            ->where('id_usuario', $idUsuario)
            ->countAllResults());
    }

    public function testBlockingAndDeactivatingInvalidateAllSessionsAndAreAudited(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.estados@example.test');
        $idUsuario = $this->crearUsuario('propietario', 'usuario.estados@example.test');
        $this->crearSesiones($idUsuario, 2);

        $servicio = new UsuarioService();
        $bloqueo = $servicio->bloquear($idUsuario, $idAdmin);

        $this->assertTrue($bloqueo['exito']);
        $this->assertSame('bloqueado', $this->estadoDe($idUsuario));
        $this->assertSame(0, $this->contarSesiones($idUsuario));

        $this->assertTrue($servicio->reactivar($idUsuario, $idAdmin)['exito']);
        $this->crearSesiones($idUsuario, 2);
        $baja = $servicio->darDeBaja($idUsuario, $idAdmin);

        $this->assertTrue($baja['exito']);
        $this->assertSame('baja', $this->estadoDe($idUsuario));
        $this->assertSame(0, $this->contarSesiones($idUsuario));
        $this->assertSame(3, $this->db->table('modificacion_usuario')
            ->where('id_usuario', $idUsuario)
            ->countAllResults());
    }

    public function testAdministratorCannotChangeOwnRoleBlockOrDeactivate(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.propio@example.test');
        $servicio = new UsuarioService();

        $actualizacion = $servicio->actualizar(
            $idAdmin,
            $idAdmin,
            'Administrador',
            'admin.propio@example.test',
            'propietario',
            'activo',
        );

        $this->assertFalse($actualizacion['exito']);
        $this->assertSame(
            'No puede quitarse el rol Administrador a sí mismo',
            $actualizacion['errores']['rol'],
        );
        $this->assertSame(
            'No puede bloquearse a sí mismo',
            $servicio->bloquear($idAdmin, $idAdmin)['errores']['estado'],
        );
        $this->assertSame(
            'No puede darse de baja a sí mismo',
            $servicio->darDeBaja($idAdmin, $idAdmin)['errores']['estado'],
        );
        $this->assertSame('admin', $this->db->table('usuario')
            ->where('id_usuario', $idAdmin)
            ->get()
            ->getRow()
            ->rol);
        $this->assertSame(0, $this->db->table('modificacion_usuario')
            ->where('id_usuario', $idAdmin)
            ->countAllResults());
    }

    public function testPasswordResetCreatesOneTimeCompliantPasswordAndRequiresChange(): void
    {
        $idAdmin = $this->crearUsuario('admin', 'admin.password@example.test');
        $idUsuario = $this->crearUsuario('propietario', 'usuario.password@example.test');
        $this->crearSesiones($idUsuario, 2);
        $hashAnterior = $this->db->table('usuario')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getRow()
            ->contrasenia;

        $resultado = (new UsuarioService())->restablecerContrasenia($idUsuario, $idAdmin);
        $usuario = $this->db->table('usuario')->where('id_usuario', $idUsuario)->get()->getRow();

        $this->assertTrue($resultado['exito']);
        $this->assertMatchesRegularExpression('/\A(?=.*[A-Za-z])(?=.*\d).{8,}\z/', $resultado['contrasenia_temporal']);
        $this->assertFalse(password_verify($resultado['contrasenia_temporal'], $hashAnterior));
        $this->assertFalse(password_verify('Clave123', $usuario->contrasenia));
        $this->assertTrue(password_verify($resultado['contrasenia_temporal'], $usuario->contrasenia));
        $this->assertSame(1, (int) $usuario->cambiar_contrasenia);
        $this->assertSame(0, $this->contarSesiones($idUsuario));
        $this->assertSame(1, $this->db->table('modificacion_usuario')
            ->where('id_usuario', $idUsuario)
            ->countAllResults());
    }

    public function testLogicalDeactivationPreservesInvoiceAndPaymentHistory(): void
    {
        $adminId = $this->crearUsuario('admin', 'admin.historial@example.test');
        $owner = $this->db->table('usuario')
            ->where('mail', 'propietario@serviinmueble.local')
            ->get()
            ->getRowArray();
        $propiedad = $this->db->table('propiedad')
            ->where('id_propietario', $owner['id_usuario'])
            ->where('alias', 'Depto Centro')
            ->get()
            ->getRowArray();
        $idsFacturas = $this->db->table('propiedad_servicio ps')
            ->select('f.id_factura')
            ->join('factura f', 'f.id_propiedad_servicio = ps.id_propiedad_servicio')
            ->where('ps.id_propiedad', $propiedad['id_propiedad'])
            ->get()
            ->getResultArray();
        $cantidadPagos = $this->db->table('transaccion')
            ->whereIn('id_factura', array_column($idsFacturas, 'id_factura'))
            ->countAllResults();

        $resultado = (new UsuarioService())->darDeBaja((int) $owner['id_usuario'], $adminId);

        $this->assertTrue($resultado['exito']);
        $this->assertSame('baja', $this->estadoDe((int) $owner['id_usuario']));
        $this->assertSame(count($idsFacturas), $this->db->table('propiedad_servicio ps')
            ->join('factura f', 'f.id_propiedad_servicio = ps.id_propiedad_servicio')
            ->where('ps.id_propiedad', $propiedad['id_propiedad'])
            ->countAllResults());
        $this->assertSame($cantidadPagos, $this->db->table('transaccion')
            ->whereIn('id_factura', array_column($idsFacturas, 'id_factura'))
            ->countAllResults());
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

    private function crearSesiones(int $idUsuario, int $cantidad): void
    {
        for ($indice = 0; $indice < $cantidad; $indice++) {
            $this->db->table('sesion')->insert([
                'id_usuario' => $idUsuario,
                'token' => bin2hex(random_bytes(32)),
            ]);
        }
    }

    private function estadoDe(int $idUsuario): string
    {
        return (string) $this->db->table('usuario')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getRow()
            ->estado;
    }

    private function contarSesiones(int $idUsuario): int
    {
        return $this->db->table('sesion')
            ->where('id_usuario', $idUsuario)
            ->countAllResults();
    }
}
