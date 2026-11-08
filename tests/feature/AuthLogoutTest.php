<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthLogoutTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';

    protected $namespace = 'App';

    protected $seed = DatabaseSeeder::class;

    public function testLogoutInvalidatesServerSessionExpiresCookieAndBlocksDashboard(): void
    {
        $this->withSession([]);

        $loginForm = $this->get('/auth/login');
        $login = $this->post('/auth/login', [
            'csrf_test_name' => $this->csrfToken($loginForm->response()->getBody()),
            'mail'           => 'propietario@serviinmueble.local',
            'contrasenia'    => 'Propietario1234',
        ]);
        $login->assertRedirectTo('/dashboard');

        $this->withSession($_SESSION);
        $idSesion = (int) ($_SESSION['id_sesion'] ?? 0);
        $this->assertGreaterThan(0, $idSesion);

        $dashboard = $this->get('/dashboard');
        $dashboard->assertOK();
        $dashboard->assertSee('Cerrar sesión');
        $this->assertStringContainsString('name="csrf_test_name"', $dashboard->response()->getBody());

        $logout = $this->post('/auth/logout', [
            'csrf_test_name' => $this->csrfToken($dashboard->response()->getBody()),
        ]);
        $logout->assertRedirectTo('/auth/login');

        $cookie = $logout->response()->getCookie(config('Session')->cookieName);
        $this->assertNotNull($cookie);
        $this->assertSame('', $cookie->getValue());
        $this->assertLessThanOrEqual(time(), $cookie->getExpiresTimestamp());
        $this->assertSame(
            0,
            db_connect('tests')->table('sesion')->where('id_sesion', $idSesion)->countAllResults(),
        );

        $this->withSession($_SESSION);
        $this->get('/dashboard')->assertRedirectTo('/auth/login');
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
