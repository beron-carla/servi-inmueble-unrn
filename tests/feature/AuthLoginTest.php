<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthLoginTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';

    protected $namespace = 'App';

    protected $seed = DatabaseSeeder::class;

    public function testLoginPageIsGuestAccessibleAndHasCsrfProtectedForm(): void
    {
        $result = $this->get('/auth/login');

        $result->assertOK();
        $result->assertSee('Iniciar sesión');
        $this->assertStringContainsString('name="csrf_test_name"', $result->response()->getBody());
        $this->assertStringContainsString('name="mail"', $result->response()->getBody());
    }

    public function testGuestIsRedirectedToLoginFromDashboard(): void
    {
        $result = $this->get('/dashboard');

        $result->assertRedirectTo('/auth/login');
    }

    public function testSuccessfulLoginRedirectsToDashboardAndDisplaysUserName(): void
    {
        $this->withSession([]);
        $result = $this->post('/auth/login', [
            'csrf_test_name' => $this->csrfToken(),
            'mail'           => 'propietario@serviinmueble.local',
            'contrasenia'    => 'Propietario1234',
        ]);
        $result->assertRedirectTo('/dashboard');

        $this->withSession($_SESSION);
        $dashboard = $this->get('/dashboard');
        $dashboard->assertOK();
        $dashboard->assertSee('Propietario Demo');
    }

    public function testTemporaryPasswordIsRedirectedToChangeBeforeDashboard(): void
    {
        $this->withSession([]);
        $result = $this->post('/auth/login', [
            'csrf_test_name' => $this->csrfToken(),
            'mail'           => 'admin@serviinmueble.local',
            'contrasenia'    => (string) env('SEED_ADMIN_PASSWORD', 'Admin1234'),
        ]);

        $result->assertRedirectTo('/auth/cambiar-contrasenia');

        $this->withSession($_SESSION);
        $dashboard = $this->get('/dashboard');
        $dashboard->assertRedirectTo('/auth/cambiar-contrasenia');
    }

    private function csrfToken(): string
    {
        $result = $this->get('/auth/login');
        $result->assertOK();

        preg_match(
            '/name="csrf_test_name"\s+value="([^"]+)"/',
            $result->response()->getBody(),
            $matches,
        );

        $this->assertNotEmpty($matches[1] ?? null, 'No se encontró el token CSRF del formulario.');

        return html_entity_decode($matches[1], ENT_QUOTES);
    }
}
