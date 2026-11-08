<?php

namespace Tests\Unit\Services;

use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthServiceTest extends CIUnitTestCase
{
    public function testRegistrationStoresAnActivePropietarioWithAHashedPassword(): void
    {
        [$service, $usuarios, $correo] = $this->crearServicio();

        $resultado = $service->registrar('  Ana Pérez  ', ' Ana@Example.Test ', 'Clave123', true);

        $this->assertTrue($resultado['exito']);
        $this->assertTrue($resultado['correo_enviado']);
        $this->assertSame([
            'nombre_completo'     => 'Ana Pérez',
            'mail'                => 'ana@example.test',
            'contrasenia'         => $usuarios->registros[0]['contrasenia'],
            'rol'                 => 'propietario',
            'estado'              => 'activo',
            'cambiar_contrasenia' => 0,
        ], $usuarios->registros[0]);
        $this->assertNotSame('Clave123', $usuarios->registros[0]['contrasenia']);
        $this->assertTrue(password_verify('Clave123', $usuarios->registros[0]['contrasenia']));
        $this->assertSame(['ana@example.test', 'Ana Pérez'], $correo->bienvenida);
    }

    public function testDuplicateEmailIsRejectedCaseInsensitivelyIncludingBajaAccounts(): void
    {
        [$service, $usuarios] = $this->crearServicio(existe: true);

        $resultado = $service->registrar('Ana Pérez', ' ANA@EXAMPLE.TEST ', 'Clave123', true);

        $this->assertFalse($resultado['exito']);
        $this->assertSame('El email ya se encuentra registrado', $resultado['errores']['mail']);
        $this->assertSame([], $usuarios->registros);
        $this->assertSame('ana@example.test', $usuarios->mailConsultado);
    }

    public function testConcurrentDuplicateEmailIsReportedAsAValidationError(): void
    {
        $usuarios = new class extends UsuarioRepository {
            private bool $creado = false;

            public function __construct() {}

            public function buscarPorMail(string $mail): ?array
            {
                return $this->creado ? ['mail' => $mail] : null;
            }

            public function crear(array $usuario): bool
            {
                $this->creado = true;

                throw new DatabaseException('Duplicate entry');
            }
        };
        $correo = new class extends EmailService {
            public function __construct() {}

            public function enviarBienvenida(string $destinatario, string $nombre): bool
            {
                return true;
            }
        };

        $resultado = (new AuthService($usuarios, $correo))
            ->registrar('Ana', 'ana@example.test', 'Clave123', true);

        $this->assertFalse($resultado['exito']);
        $this->assertSame('El email ya se encuentra registrado', $resultado['errores']['mail']);
    }

    public function testRegistrationReturnsTheSpecifiedRequiredAndFormatMessages(): void
    {
        [$service] = $this->crearServicio();

        $resultado = $service->registrar('', '', '', false);

        $this->assertSame('El nombre es obligatorio', $resultado['errores']['nombre_completo']);
        $this->assertSame('El email es obligatorio', $resultado['errores']['mail']);
        $this->assertSame('La contraseña es obligatoria', $resultado['errores']['contrasenia']);
        $this->assertSame('Debe aceptar los términos y condiciones', $resultado['errores']['terminos']);

        $emailInvalido = $service->registrar('Ana', 'no-es-un-email', 'Clave123', true);
        $this->assertSame('El email debe tener formato válido', $emailInvalido['errores']['mail']);
    }

    public function testRegistrationEnforcesPasswordLengthAndComposition(): void
    {
        [$service] = $this->crearServicio();

        $corta = $service->registrar('Ana', 'ana@example.test', 'Abc1234', true);
        $sinNumero = $service->registrar('Ana', 'ana@example.test', 'abcdefgh', true);
        $sinLetra = $service->registrar('Ana', 'ana@example.test', '12345678', true);

        $this->assertSame('Mínimo 8 caracteres', $corta['errores']['contrasenia']);
        $this->assertSame(
            'Debe contener al menos una letra y un número',
            $sinNumero['errores']['contrasenia']
        );
        $this->assertSame(
            'Debe contener al menos una letra y un número',
            $sinLetra['errores']['contrasenia']
        );
    }

    public function testRegistrationReportsWhenWelcomeEmailCannotBeSent(): void
    {
        [$service] = $this->crearServicio(correoEnviado: false);

        $resultado = $service->registrar('Ana', 'ana@example.test', 'Clave123', true);

        $this->assertTrue($resultado['exito']);
        $this->assertFalse($resultado['correo_enviado']);
    }

    /**
     * @return array{AuthService, UsuarioRepository&object, EmailService&object}
     */
    private function crearServicio(bool $existe = false, bool $correoEnviado = true): array
    {
        $usuarios = new class($existe) extends UsuarioRepository {
            /** @var list<array<string, mixed>> */
            public array $registros = [];

            public ?string $mailConsultado = null;

            public function __construct(private bool $existe) {}

            public function buscarPorMail(string $mail): ?array
            {
                $this->mailConsultado = $mail;

                if ($this->existe) {
                    return ['mail' => $mail, 'estado' => 'baja'];
                }

                foreach ($this->registros as $registro) {
                    if ($registro['mail'] === $mail) {
                        return $registro;
                    }
                }

                return null;
            }

            public function crear(array $usuario): bool
            {
                $this->registros[] = $usuario;

                return true;
            }
        };

        $correo = new class($correoEnviado) extends EmailService {
            /** @var list<string> */
            public array $bienvenida = [];

            public function __construct(private bool $enviado) {}

            public function enviarBienvenida(string $destinatario, string $nombre): bool
            {
                $this->bienvenida = [$destinatario, $nombre];

                return $this->enviado;
            }
        };

        return [new AuthService($usuarios, $correo), $usuarios, $correo];
    }
}
