<?php

namespace Tests\Unit\Services;

use App\Repositories\UsuarioRepository;
use App\Services\UsuarioService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UsuarioServiceTest extends CIUnitTestCase
{
    public function testCreatesAnActiveUserWithNormalizedEmailAndHashedPassword(): void
    {
        [$service, $usuarios] = $this->crearServicio();

        $resultado = $service->crear(' Ana Pérez ', ' Ana@Example.Test ', 'Clave123', 'propietario');

        $this->assertTrue($resultado['exito']);
        $this->assertSame([], $resultado['errores']);
        $this->assertSame('Ana Pérez', $usuarios->registros[0]['nombre_completo']);
        $this->assertSame('ana@example.test', $usuarios->registros[0]['mail']);
        $this->assertSame('propietario', $usuarios->registros[0]['rol']);
        $this->assertSame('activo', $usuarios->registros[0]['estado']);
        $this->assertSame(0, $usuarios->registros[0]['cambiar_contrasenia']);
        $this->assertNotSame('Clave123', $usuarios->registros[0]['contrasenia']);
        $this->assertTrue(password_verify('Clave123', $usuarios->registros[0]['contrasenia']));
    }

    public function testAllowsCreatingAnAdministrator(): void
    {
        [$service, $usuarios] = $this->crearServicio();

        $resultado = $service->crear('Admin', 'admin@example.test', 'Clave123', 'admin');

        $this->assertTrue($resultado['exito']);
        $this->assertSame('admin', $usuarios->registros[0]['rol']);
    }

    public function testCreatedUserAppearsInTheUserList(): void
    {
        [$service] = $this->crearServicio();

        $service->crear('Ana Pérez', 'ana@example.test', 'Clave123', 'propietario');

        $this->assertSame('ana@example.test', $service->listar()['usuarios'][0]['mail']);
    }

    public function testUserListNormalizesFiltersAndPageBeforeQuerying(): void
    {
        [$service, $usuarios] = $this->crearServicio();

        $service->listar([
            'q' => '  Juan  ',
            'rol' => 'admin',
            'estado' => 'baja',
            'page' => '2',
        ]);

        $this->assertSame(['q' => 'Juan', 'rol' => 'admin', 'estado' => 'baja'], $usuarios->filtrosConsultados);
        $this->assertSame(2, $usuarios->paginaConsultada);
    }

    public function testUserListIgnoresInvalidFiltersAndPage(): void
    {
        [$service, $usuarios] = $this->crearServicio();

        $resultado = $service->listar([
            'q' => ['no válido'],
            'rol' => 'superusuario',
            'estado' => 'eliminado',
            'page' => '0',
        ]);

        $this->assertSame(['q' => '', 'rol' => '', 'estado' => ''], $usuarios->filtrosConsultados);
        $this->assertSame(1, $usuarios->paginaConsultada);
        $this->assertSame(['q' => '', 'rol' => '', 'estado' => ''], $resultado['filtros']);
    }

    public function testReturnsRequiredValidationMessages(): void
    {
        [$service] = $this->crearServicio();

        $resultado = $service->crear('', '', '', '');

        $this->assertSame('El nombre es obligatorio', $resultado['errores']['nombre_completo']);
        $this->assertSame('El email es obligatorio', $resultado['errores']['mail']);
        $this->assertSame('La contraseña es obligatoria', $resultado['errores']['contrasenia']);
        $this->assertSame('El rol es obligatorio', $resultado['errores']['rol']);
    }

    public function testReturnsPasswordAndFormatValidationMessages(): void
    {
        [$service] = $this->crearServicio();

        $emailInvalido = $service->crear('Ana', 'no-es-un-email', 'Clave123', 'propietario');
        $corta = $service->crear('Ana', 'ana@example.test', 'Abc1234', 'propietario');
        $sinNumero = $service->crear('Ana', 'ana@example.test', 'abcdefgh', 'propietario');
        $sinLetra = $service->crear('Ana', 'ana@example.test', '12345678', 'propietario');
        $rolInvalido = $service->crear('Ana', 'ana@example.test', 'Clave123', 'Superusuario');

        $this->assertSame('El email debe tener formato válido', $emailInvalido['errores']['mail']);
        $this->assertSame(
            'Mínimo 8 caracteres de contraseña',
            $corta['errores']['contrasenia']
        );
        $this->assertSame(
            'Debe contener al menos una letra y un número',
            $sinNumero['errores']['contrasenia']
        );
        $this->assertSame(
            'Debe contener al menos una letra y un número',
            $sinLetra['errores']['contrasenia']
        );
        $this->assertSame('Rol inválido', $rolInvalido['errores']['rol']);
    }

    public function testRejectsDuplicateEmailIncludingUsersInBaja(): void
    {
        [$service, $usuarios] = $this->crearServicio(existe: true);

        $resultado = $service->crear('Ana', ' ANA@EXAMPLE.TEST ', 'Clave123', 'propietario');

        $this->assertFalse($resultado['exito']);
        $this->assertSame('El email ya se encuentra registrado', $resultado['errores']['mail']);
        $this->assertSame([], $usuarios->registros);
        $this->assertSame('ana@example.test', $usuarios->mailConsultado);
    }

    public function testReportsConcurrentDuplicateEmailWithoutHidingOtherDatabaseErrors(): void
    {
        $usuarios = new class extends UsuarioRepository {
            private bool $creado = false;

            public function __construct() {}

            public function buscarPorMail(string $mail): ?array
            {
                return $this->creado ? ['mail' => $mail, 'estado' => 'baja'] : null;
            }

            public function crear(array $usuario): bool
            {
                $this->creado = true;

                throw new DatabaseException('Duplicate entry');
            }
        };
        $service = new UsuarioService($usuarios);

        $resultado = $service->crear('Ana', 'ana@example.test', 'Clave123', 'propietario');

        $this->assertFalse($resultado['exito']);
        $this->assertSame('El email ya se encuentra registrado', $resultado['errores']['mail']);
    }

    /**
     * @return array{UsuarioService, UsuarioRepository&object}
     */
    private function crearServicio(bool $existe = false): array
    {
        $usuarios = new class($existe) extends UsuarioRepository {
            /** @var list<array<string, mixed>> */
            public array $registros = [];

            /** @var array{q: string, rol: string, estado: string} */
            public array $filtrosConsultados = ['q' => '', 'rol' => '', 'estado' => ''];

            public int $paginaConsultada = 1;

            public ?string $mailConsultado = null;

            public function __construct(private bool $existe) {}

            public function buscarPorMail(string $mail): ?array
            {
                $this->mailConsultado = $mail;

                if ($this->existe) {
                    return ['mail' => $mail, 'estado' => 'baja'];
                }

                return null;
            }

            public function crear(array $usuario): bool
            {
                $this->registros[] = $usuario;

                return true;
            }

            public function listar(array $filtros = [], int $pagina = 1): array
            {
                $this->filtrosConsultados = $filtros;
                $this->paginaConsultada = $pagina;

                return ['usuarios' => $this->registros, 'pager' => null];
            }
        };

        return [new UsuarioService($usuarios), $usuarios];
    }
}
