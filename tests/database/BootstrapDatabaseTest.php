<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Filters\AuthFilter;
use App\Repositories\SesionRepository;
use App\Services\SesionService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * @internal
 */
final class BootstrapDatabaseTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testMigrationsCreateCanonicalTablesAndBothTriggers(): void
    {
        $this->assertSame('serviinmueble_test', $this->db->getDatabase());

        $tables = [
            'tipo_inmueble',
            'tipo_servicio',
            'direccion',
            'usuario',
            'modificacion_usuario',
            'sesion',
            'propiedad',
            'propiedad_servicio',
            'factura',
            'transaccion',
            'historico_login',
        ];

        foreach ($tables as $table) {
            $result = $this->db->query(
                'SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$this->db->getDatabase(), $table],
            )->getRowArray();

            $this->assertSame(1, (int) $result['total'], "No existe la tabla {$table}.");
        }

        $triggers = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = ?',
            [$this->db->getDatabase()],
        )->getRowArray();

        $this->assertSame(2, (int) $triggers['total']);
    }

    public function testCatalogsAndAdminSeedAreIdempotentAndPasswordIsVerifiable(): void
    {
        $this->assertSame(5, $this->db->table('tipo_inmueble')->countAllResults());
        $this->assertSame(9, $this->db->table('tipo_servicio')->countAllResults());

        $email = strtolower((string) env('SEED_ADMIN_EMAIL', 'admin@serviinmueble.local'));
        $admin = $this->db->table('usuario')->where('mail', $email)->get()->getRowArray();
        $password = (string) env('SEED_ADMIN_PASSWORD', 'Admin1234');

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin['rol']);
        $this->assertSame('activo', $admin['estado']);
        $this->assertSame(1, (int) $admin['cambiar_contrasenia']);
        $this->assertTrue(password_verify($password, $admin['contrasenia']));

        $before = $this->counts();
        $this->seeder->call(DatabaseSeeder::class);
        $this->seeder->call(DatabaseSeeder::class);

        $this->assertSame($before, $this->counts());
    }

    public function testDemoSeederCreatesExpectedRecordsOnlyOutsideProduction(): void
    {
        $this->assertSame(1, $this->db->table('propiedad')->where('alias', 'Depto Centro')->countAllResults());
        $this->assertSame(1, $this->db->table('propiedad_servicio')->countAllResults());
        $this->assertSame(3, $this->db->table('factura')->countAllResults());
        $this->assertSame(1, $this->db->table('transaccion')->countAllResults());
    }

    public function testSesionServiceInvalidatesAllSessionsForAUser(): void
    {
        $owner = $this->db->table('usuario')->where('mail', 'propietario@serviinmueble.local')->get()->getRowArray();

        $this->db->table('sesion')->insertBatch([
            ['id_usuario' => $owner['id_usuario'], 'token' => str_repeat('b', 64)],
            ['id_usuario' => $owner['id_usuario'], 'token' => str_repeat('c', 64)],
        ]);

        $this->assertTrue((new SesionService(new SesionRepository($this->db)))
            ->invalidarPorUsuario((int) $owner['id_usuario']));
        $this->assertSame(0, $this->db->table('sesion')->where('id_usuario', $owner['id_usuario'])->countAllResults());
    }

    public function testAuthFilterDeletesSessionsInactiveForMoreThanThirtyMinutes(): void
    {
        $owner = $this->db->table('usuario')->where('mail', 'propietario@serviinmueble.local')->get()->getRowArray();
        $token = str_repeat('d', 64);
        $this->db->table('sesion')->insert([
            'id_usuario' => $owner['id_usuario'],
            'token' => $token,
            'ultima_actividad' => date('Y-m-d H:i:s', time() - 1801),
        ]);
        $sessionId = $this->db->insertID();
        session()->set([
            'id_usuario' => (int) $owner['id_usuario'],
            'id_sesion' => $sessionId,
            'token' => $token,
            'rol' => 'propietario',
        ]);

        $request = service('incomingrequest');
        $response = (new AuthFilter())->before($request);

        $this->assertNotNull($response);
        $this->assertSame(0, $this->db->table('sesion')->where('id_sesion', $sessionId)->countAllResults());
    }

    public function testTemporaryPasswordRestrictsProtectedRoutesToPasswordChange(): void
    {
        $owner = $this->db->table('usuario')
            ->where('mail', 'propietario@serviinmueble.local')
            ->get()
            ->getRowArray();
        $this->db->table('usuario')
            ->where('id_usuario', $owner['id_usuario'])
            ->update(['cambiar_contrasenia' => 1]);

        $token = str_repeat('e', 64);
        $this->db->table('sesion')->insert([
            'id_usuario' => $owner['id_usuario'],
            'token' => $token,
            'ultima_actividad' => date('Y-m-d H:i:s'),
        ]);
        $sessionId = $this->db->insertID();
        session()->set([
            'id_usuario' => (int) $owner['id_usuario'],
            'id_sesion' => $sessionId,
            'token' => $token,
            'rol' => 'propietario',
        ]);

        $request = service('incomingrequest');
        $request->getUri()->setPath('/dashboard');
        $response = (new AuthFilter())->before($request);

        $this->assertNotNull($response);
        $this->assertSame(
            site_url('auth/cambiar-contrasenia'),
            $response->getHeaderLine('Location'),
        );
    }

    public function testTipoServicioTriggerRejectsIncompatibleUnit(): void
    {
        $this->expectException(DatabaseException::class);
        $this->db->table('tipo_servicio')->insert([
            'tipo' => 'electricidad',
            'categoria' => 'publico',
            'unidad_medida' => 'm³',
            'proveedor_predeterminado' => 'Proveedor inválido',
            'activo' => 1,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'tipo_inmueble' => $this->db->table('tipo_inmueble')->countAllResults(),
            'tipo_servicio' => $this->db->table('tipo_servicio')->countAllResults(),
            'usuario' => $this->db->table('usuario')->countAllResults(),
            'propiedad' => $this->db->table('propiedad')->countAllResults(),
            'propiedad_servicio' => $this->db->table('propiedad_servicio')->countAllResults(),
            'factura' => $this->db->table('factura')->countAllResults(),
            'transaccion' => $this->db->table('transaccion')->countAllResults(),
        ];
    }
}
