<?php

use App\Controllers\BaseController;
use App\Filters\RoleFilter;
use App\Services\EmailService;
use CodeIgniter\Email\Email;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\App;
use Config\Database;
use Config\Email as EmailConfig;
use Config\Filters;
use Config\Routing;
use Config\Session;

/**
 * @internal
 */
final class BootstrapConfigTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testBootstrapConfigurationUsesSpanishArgentinaAndMySqlTests(): void
    {
        $app = new App();
        $session = new Session();
        $filters = new Filters();
        $email = new EmailConfig();
        $database = new Database();
        $routing = new Routing();

        $this->assertSame('', $app->indexPage);
        $this->assertSame('es', $app->defaultLocale);
        $this->assertSame(['es'], $app->supportedLocales);
        $this->assertSame('America/Argentina/Buenos_Aires', $app->appTimezone);
        $this->assertSame(1800, $session->expiration);
        $this->assertTrue($session->regenerateDestroy);
        $this->assertSame(300, $session->timeToUpdate);
        $this->assertArrayHasKey('auth', $filters->aliases);
        $this->assertArrayHasKey('guest', $filters->aliases);
        $this->assertArrayHasKey('role', $filters->aliases);
        $this->assertContains('csrf', $filters->globals['before']);
        $this->assertContains('secureheaders', $filters->globals['after']);
        $this->assertSame('smtp', $email->protocol);
        $this->assertSame('localhost', $email->SMTPHost);
        $this->assertSame(1025, $email->SMTPPort);
        $this->assertSame('html', $email->mailType);
        $this->assertSame('MySQLi', $database->tests['DBDriver']);
        $this->assertSame('serviinmueble_test', $database->tests['database']);
        $this->assertFalse($routing->autoRoute);
        $this->assertSame('/dashboard', route_to('dashboard'));
        $this->assertSame('El campo Nombre es obligatorio.', lang('Validation.required', ['field' => 'Nombre']));
    }

    public function testPostWithoutCsrfTokenIsForbidden(): void
    {
        $this->withRoutes([
            ['POST', 'bootstrap-csrf-probe', static fn (): string => 'sin protección'],
        ]);

        try {
            $this->post('bootstrap-csrf-probe');
            $this->fail('El POST sin token CSRF debe ser rechazado.');
        } catch (SecurityException $exception) {
            $this->assertSame(403, $exception->getCode());
        }
    }

    public function testRoleFilterReturnsForbiddenViewForDisallowedRole(): void
    {
        session()->set('rol', 'propietario');
        $request = service('incomingrequest');

        $response = (new RoleFilter())->before($request, ['admin']);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString('Volver al dashboard', $response->getBody());
    }

    public function testAuthLayoutRendersSpanishLoginContainer(): void
    {
        $html = view('layout/auth', ['titulo' => 'Acceso de prueba']);

        $this->assertStringContainsString('<html lang="es">', $html);
        $this->assertStringContainsString('login-box', $html);
        $this->assertStringContainsString('Acceso de prueba', $html);
    }

    public function testAllModelsDisableFrameworkTimestamps(): void
    {
        $models = [
            \App\Models\UsuarioModel::class,
            \App\Models\TipoInmuebleModel::class,
            \App\Models\TipoServicioModel::class,
            \App\Models\DireccionModel::class,
            \App\Models\PropiedadModel::class,
            \App\Models\PropiedadServicioModel::class,
            \App\Models\FacturaModel::class,
            \App\Models\TransaccionModel::class,
            \App\Models\SesionModel::class,
            \App\Models\HistoricoLoginModel::class,
            \App\Models\ModificacionUsuarioModel::class,
        ];

        foreach ($models as $modelClass) {
            $model = new $modelClass();
            $timestamps = new \ReflectionProperty($model, 'useTimestamps');

            $this->assertFalse($timestamps->getValue($model), "{$modelClass} no debe inventar columnas de auditoría.");
        }
    }

    public function testBaseControllerLoadsBootstrapHelpers(): void
    {
        $controller = new class extends BaseController {};
        $helpers = new \ReflectionProperty(BaseController::class, 'helpers');

        $this->assertSame(['form', 'url', 'auth', 'format'], $helpers->getValue($controller));
    }

    public function testEmailServiceCanUseAUnitTestDouble(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())->method('setFrom')->willReturn(true);
        $email->expects($this->once())->method('setTo')->with('persona@example.test')->willReturn(true);
        $email->expects($this->once())->method('setSubject')->willReturn(true);
        $email->expects($this->once())->method('setMessage')
            ->with($this->callback(static function (string $message): bool {
                return str_contains($message, 'Nombre de prueba')
                    && str_contains($message, 'auth/login');
            }))
            ->willReturn(true);
        $email->expects($this->once())->method('send')->willReturn(true);

        $this->assertTrue((new EmailService($email))->enviarBienvenida('persona@example.test', 'Nombre de prueba'));
    }
}
