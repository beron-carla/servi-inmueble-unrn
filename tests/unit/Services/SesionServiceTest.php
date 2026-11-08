<?php

namespace Tests\Unit\Services;

use App\Repositories\SesionRepository;
use App\Services\SesionService;
use CodeIgniter\Session\SessionInterface;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SesionServiceTest extends CIUnitTestCase
{
    public function testStartingSessionRegeneratesIdAndStoresRandomToken(): void
    {
        $repository = new class extends SesionRepository {
            public ?string $tokenGuardado = null;

            public function __construct() {}

            public function crear(int $idUsuario, string $token): int|false
            {
                $this->tokenGuardado = $token;

                return 42;
            }
        };
        $datosSesion = [];
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('regenerate')->with(true);
        $session->expects($this->once())->method('set')
            ->willReturnCallback(static function (array $datos) use (&$datosSesion): void {
                $datosSesion = $datos;
            });

        (new SesionService($repository, $session))->iniciar([
            'id_usuario'      => 7,
            'nombre_completo' => 'Ana Pérez',
            'rol'             => 'propietario',
        ]);

        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', (string) $repository->tokenGuardado);
        $this->assertSame(7, $datosSesion['id_usuario']);
        $this->assertSame(42, $datosSesion['id_sesion']);
        $this->assertSame($repository->tokenGuardado, $datosSesion['token']);
        $this->assertSame('Ana Pérez', $datosSesion['nombre_completo']);
    }

    public function testLogoutInvalidatesPersistedSessionAndRemovesAuthenticationData(): void
    {
        $repository = new class extends SesionRepository {
            public ?int $sesionEliminada = null;

            public function __construct() {}

            public function eliminar(int $idSesion): bool
            {
                $this->sesionEliminada = $idSesion;

                return true;
            }
        };
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('get')->with('id_sesion')->willReturn(42);
        $session->expects($this->once())->method('remove')
            ->with(['id_usuario', 'id_sesion', 'token', 'rol', 'nombre_completo']);
        $session->expects($this->once())->method('destroy');

        (new SesionService($repository, $session))->cerrarSesion();

        $this->assertSame(42, $repository->sesionEliminada);
    }
}
