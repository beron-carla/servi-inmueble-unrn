<?php

namespace Tests\Unit\Services;

use App\Repositories\UsuarioRepository;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\LoginThrottleService;
use App\Services\SesionService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthServiceLoginTest extends CIUnitTestCase
{
    public function testSuccessfulLoginNormalizesEmailAndCreatesSession(): void
    {
        [$service, $usuarios, $throttle, $sesiones] = $this->crearServicio($this->usuario());

        $resultado = $service->iniciarSesion(' Ana@Example.Test ', 'Correct123', '192.0.2.10');

        $this->assertSame(['exito' => true, 'cambiar_contrasenia' => true], $resultado);
        $this->assertSame('ana@example.test', $usuarios->emailConsultado);
        $this->assertSame('Ana Pérez', $sesiones->usuarioIniciado['nombre_completo']);
        $this->assertSame([['ana@example.test', '192.0.2.10', true]], $throttle->intentos);
    }

    public function testUnknownEmailAndWrongPasswordHaveTheSameGenericMessage(): void
    {
        [$servicioSinUsuario] = $this->crearServicio(null);
        [$servicioPasswordIncorrecto] = $this->crearServicio($this->usuario());

        $sinUsuario = $servicioSinUsuario->iniciarSesion('ana@example.test', 'Incorrect123', '192.0.2.10');
        $passwordIncorrecto = $servicioPasswordIncorrecto
            ->iniciarSesion('ana@example.test', 'Incorrect123', '192.0.2.10');

        $this->assertSame('Email o contraseña incorrectos', $sinUsuario['mensaje']);
        $this->assertSame($sinUsuario['mensaje'], $passwordIncorrecto['mensaje']);
    }

    public function testAccountStateIsReportedOnlyAfterPasswordVerification(): void
    {
        foreach ([
            'bloqueado' => 'Usuario bloqueado, contacte al administrador',
            'baja'      => 'Usuario dado de baja, contacte al administrador',
        ] as $estado => $mensaje) {
            [$service] = $this->crearServicio($this->usuario($estado));

            $incorrecto = $service->iniciarSesion('ana@example.test', 'Incorrect123', '192.0.2.10');
            $correcto = $service->iniciarSesion('ana@example.test', 'Correct123', '192.0.2.10');

            $this->assertSame('Email o contraseña incorrectos', $incorrecto['mensaje']);
            $this->assertSame($mensaje, $correcto['mensaje']);
        }
    }

    public function testRateLimitedLoginDoesNotAcceptCorrectCredentials(): void
    {
        [$service, , $throttle, $sesiones] = $this->crearServicio($this->usuario());
        $throttle->bloqueado = true;

        $resultado = $service->iniciarSesion('ana@example.test', 'Correct123', '192.0.2.10');

        $this->assertSame('Demasiados intentos, intente en 15 minutos', $resultado['mensaje']);
        $this->assertSame([], $sesiones->usuarioIniciado);
        $this->assertSame([['ana@example.test', '192.0.2.10', false]], $throttle->intentos);
    }

    public function testFifthFailedAttemptStartsTheCooldown(): void
    {
        [$service, , $throttle] = $this->crearServicio(null);
        $throttle->bloquearAlQuintoFallo = true;

        for ($intento = 1; $intento <= 5; $intento++) {
            $resultado = $service->iniciarSesion('ana@example.test', 'Incorrect123', '192.0.2.10');
        }

        $this->assertSame('Demasiados intentos, intente en 15 minutos', $resultado['mensaje']);
        $this->assertCount(5, $throttle->intentos);
    }

    public function testTemporaryPasswordChangeRejectsReuseAndClearsForcedChangeFlag(): void
    {
        [$service, $usuarios, , $sesiones] = $this->crearServicio($this->usuario());
        $hashAnterior = $usuarios->usuario['contrasenia'];

        $repetida = $service->cambiarContrasenia(12, 'Correct123');
        $this->assertSame('La nueva contraseña no puede ser igual a la anterior', $repetida['mensaje']);

        $actualizada = $service->cambiarContrasenia(12, 'NuevaClave123');

        $this->assertTrue($actualizada['exito']);
        $this->assertNotSame($hashAnterior, $usuarios->usuario['contrasenia']);
        $this->assertTrue(password_verify('NuevaClave123', $usuarios->usuario['contrasenia']));
        $this->assertSame(0, $usuarios->usuario['cambiar_contrasenia']);
        $this->assertSame(1, $sesiones->regeneraciones);
    }

    /**
     * @return array{AuthService, UsuarioRepository&object, LoginThrottleService&object, SesionService&object}
     */
    private function crearServicio(?array $usuario): array
    {
        $usuarios = new class($usuario) extends UsuarioRepository {
            public ?string $emailConsultado = null;

            public ?array $usuario;

            public function __construct(?array $usuario)
            {
                $this->usuario = $usuario;
            }

            public function buscarPorMail(string $mail): ?array
            {
                $this->emailConsultado = $mail;

                return $this->usuario;
            }

            public function buscarPorId(int $id): ?array
            {
                return $this->usuario;
            }

            public function actualizarContrasenia(int $idUsuario, string $hash, bool $cambiarContrasenia): bool
            {
                $this->usuario['contrasenia'] = $hash;
                $this->usuario['cambiar_contrasenia'] = $cambiarContrasenia ? 1 : 0;

                return true;
            }
        };

        $email = new class extends EmailService {
            public function __construct() {}

            public function enviarBienvenida(string $destinatario, string $nombre): bool
            {
                return true;
            }
        };

        $throttle = new class extends LoginThrottleService {
            /** @var list<array{string, string, bool}> */
            public array $intentos = [];

            public bool $bloqueado = false;

            public bool $bloquearAlQuintoFallo = false;

            public function __construct() {}

            public function estaBloqueado(string $email, string $ip): bool
            {
                return $this->bloqueado;
            }

            public function registrarIntento(string $email, string $ip, bool $exitoso): void
            {
                $this->intentos[] = [$email, $ip, $exitoso];

                if ($this->bloquearAlQuintoFallo && count($this->intentos) >= 5) {
                    $this->bloqueado = true;
                }
            }
        };

        $sesiones = new class extends SesionService {
            /** @var array<string, mixed> */
            public array $usuarioIniciado = [];

            public int $regeneraciones = 0;

            public function __construct() {}

            public function iniciar(array $usuario): void
            {
                $this->usuarioIniciado = $usuario;
            }

            public function regenerar(): void
            {
                $this->regeneraciones++;
            }
        };

        return [new AuthService($usuarios, $email, $throttle, $sesiones), $usuarios, $throttle, $sesiones];
    }

    /**
     * @return array<string, mixed>
     */
    private function usuario(string $estado = 'activo'): array
    {
        return [
            'id_usuario'          => 12,
            'nombre_completo'     => 'Ana Pérez',
            'mail'                => 'ana@example.test',
            'contrasenia'         => password_hash('Correct123', PASSWORD_BCRYPT),
            'rol'                 => 'propietario',
            'estado'              => $estado,
            'cambiar_contrasenia' => 1,
        ];
    }
}
