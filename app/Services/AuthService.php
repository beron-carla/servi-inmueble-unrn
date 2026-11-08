<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use CodeIgniter\Database\Exceptions\DatabaseException;
use RuntimeException;

class AuthService
{
    private UsuarioRepository $usuarios;

    private EmailService $emailService;

    private LoginThrottleService $loginThrottle;

    private SesionService $sesiones;

    public function __construct(
        ?UsuarioRepository $usuarios = null,
        ?EmailService $emailService = null,
        ?LoginThrottleService $loginThrottle = null,
        ?SesionService $sesiones = null,
    ) {
        $this->usuarios = $usuarios ?? new UsuarioRepository();
        $this->emailService = $emailService ?? new EmailService();
        $this->loginThrottle = $loginThrottle ?? new LoginThrottleService();
        $this->sesiones = $sesiones ?? new SesionService();
    }

    /**
     * @return array{exito: bool, mensaje?: string, cambiar_contrasenia?: bool}
     */
    public function iniciarSesion(string $mail, string $contrasenia, string $ip): array
    {
        $mail = mb_strtolower(trim($mail), 'UTF-8');
        $mailRegistro = mb_substr($mail, 0, 100, 'UTF-8');
        $ip = trim($ip);

        if ($ip === '') {
            throw new RuntimeException('No se pudo determinar la dirección IP del intento de inicio de sesión.');
        }

        if ($this->loginThrottle->estaBloqueado($mailRegistro, $ip)) {
            $this->loginThrottle->registrarIntento($mailRegistro, $ip, false);

            return ['exito' => false, 'mensaje' => 'Demasiados intentos, intente en 15 minutos'];
        }

        $emailValido = strlen($mail) <= 100 && filter_var($mail, FILTER_VALIDATE_EMAIL) !== false;
        $usuario = $emailValido ? $this->usuarios->buscarPorMail($mail) : null;
        $credencialesValidas = $usuario !== null
            && password_verify($contrasenia, (string) $usuario['contrasenia']);

        if (! $credencialesValidas) {
            $this->loginThrottle->registrarIntento($mailRegistro, $ip, false);

            return [
                'exito'  => false,
                'mensaje' => $this->loginThrottle->estaBloqueado($mailRegistro, $ip)
                    ? 'Demasiados intentos, intente en 15 minutos'
                    : 'Email o contraseña incorrectos',
            ];
        }

        if ($usuario['estado'] !== 'activo') {
            $this->loginThrottle->registrarIntento($mailRegistro, $ip, false);

            if ($this->loginThrottle->estaBloqueado($mailRegistro, $ip)) {
                return ['exito' => false, 'mensaje' => 'Demasiados intentos, intente en 15 minutos'];
            }

            return [
                'exito'  => false,
                'mensaje' => $usuario['estado'] === 'bloqueado'
                    ? 'Usuario bloqueado, contacte al administrador'
                    : 'Usuario dado de baja, contacte al administrador',
            ];
        }

        $this->sesiones->iniciar($usuario);
        $this->loginThrottle->registrarIntento($mailRegistro, $ip, true);

        return [
            'exito'                => true,
            'cambiar_contrasenia' => (bool) $usuario['cambiar_contrasenia'],
        ];
    }

    /**
     * @return array{exito: bool, mensaje?: string}
     */
    public function cambiarContrasenia(int $idUsuario, string $nuevaContrasenia): array
    {
        $error = $this->validarContrasenia($nuevaContrasenia);

        if ($error !== null) {
            return ['exito' => false, 'mensaje' => $error];
        }

        $usuario = $this->usuarios->buscarPorId($idUsuario);

        if ($usuario === null) {
            throw new RuntimeException('No se encontró el usuario autenticado.');
        }

        if (password_verify($nuevaContrasenia, (string) $usuario['contrasenia'])) {
            return ['exito' => false, 'mensaje' => 'La nueva contraseña no puede ser igual a la anterior'];
        }

        $algoritmo = in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_BCRYPT;
        $hash = password_hash($nuevaContrasenia, $algoritmo);

        if ($hash === false) {
            throw new RuntimeException('No se pudo proteger la nueva contraseña.');
        }

        if (! $this->usuarios->actualizarContrasenia($idUsuario, $hash, false)) {
            throw new RuntimeException('No se pudo actualizar la contraseña.');
        }

        $this->sesiones->regenerar();

        return ['exito' => true];
    }

    public function cerrarSesion(): void
    {
        $this->sesiones->cerrarSesion();
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, correo_enviado?: bool}
     */
    public function registrar(string $nombre, string $mail, string $contrasenia, bool $aceptoTerminos): array
    {
        $nombre = trim($nombre);
        $mail = strtolower(trim($mail));
        $errores = [];

        if ($nombre === '') {
            $errores['nombre_completo'] = 'El nombre es obligatorio';
        } elseif (mb_strlen($nombre, 'UTF-8') > 50) {
            $errores['nombre_completo'] = 'El nombre no puede superar los 50 caracteres';
        }

        if ($mail === '') {
            $errores['mail'] = 'El email es obligatorio';
        } elseif (! filter_var($mail, FILTER_VALIDATE_EMAIL) || strlen($mail) > 100) {
            $errores['mail'] = 'El email debe tener formato válido';
        }

        if ($contrasenia === '') {
            $errores['contrasenia'] = 'La contraseña es obligatoria';
        } elseif (strlen($contrasenia) < 8) {
            $errores['contrasenia'] = 'Mínimo 8 caracteres';
        } elseif (! preg_match('/[A-Za-z]/', $contrasenia) || ! preg_match('/\d/', $contrasenia)) {
            $errores['contrasenia'] = 'Debe contener al menos una letra y un número';
        }

        if (! $aceptoTerminos) {
            $errores['terminos'] = 'Debe aceptar los términos y condiciones';
        }

        if ($errores !== []) {
            return ['exito' => false, 'errores' => $errores];
        }

        if ($this->usuarios->buscarPorMail($mail) !== null) {
            return [
                'exito' => false,
                'errores' => ['mail' => 'El email ya se encuentra registrado'],
            ];
        }

        $algoritmo = in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_BCRYPT;
        $hash = password_hash($contrasenia, $algoritmo);

        if ($hash === false) {
            throw new RuntimeException('No se pudo proteger la contraseña del nuevo usuario.');
        }

        $usuario = [
            'nombre_completo'     => $nombre,
            'mail'                => $mail,
            'contrasenia'         => $hash,
            'rol'                 => 'propietario',
            'estado'              => 'activo',
            'cambiar_contrasenia' => 0,
        ];

        try {
            $creado = $this->usuarios->crear($usuario);
        } catch (DatabaseException $exception) {
            if ($this->usuarios->buscarPorMail($mail) !== null) {
                return [
                    'exito' => false,
                    'errores' => ['mail' => 'El email ya se encuentra registrado'],
                ];
            }

            throw $exception;
        }

        if (! $creado) {
            if ($this->usuarios->buscarPorMail($mail) !== null) {
                return [
                    'exito' => false,
                    'errores' => ['mail' => 'El email ya se encuentra registrado'],
                ];
            }

            throw new RuntimeException('No se pudo crear la cuenta. Intente nuevamente.');
        }

        $correoEnviado = $this->emailService->enviarBienvenida($mail, $nombre);

        if (! $correoEnviado) {
            log_message('error', 'No se pudo enviar el correo de bienvenida de un nuevo registro.');
        }

        return [
            'exito'          => true,
            'errores'        => [],
            'correo_enviado' => $correoEnviado,
        ];
    }

    private function validarContrasenia(string $contrasenia): ?string
    {
        if ($contrasenia === '') {
            return 'La contraseña es obligatoria';
        }

        if (strlen($contrasenia) < 8) {
            return 'Mínimo 8 caracteres';
        }

        if (! preg_match('/[A-Za-z]/', $contrasenia) || ! preg_match('/\d/', $contrasenia)) {
            return 'Debe contener al menos una letra y un número';
        }

        return null;
    }
}
