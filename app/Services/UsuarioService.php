<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use RuntimeException;
use Throwable;

class UsuarioService
{
    private SesionService $sesiones;

    private AuditoriaService $auditoria;

    private BaseConnection $db;

    public function __construct(
        private ?UsuarioRepository $usuarios = null,
        ?SesionService $sesiones = null,
        ?AuditoriaService $auditoria = null,
        ?BaseConnection $db = null,
    ) {
        $this->usuarios ??= new UsuarioRepository();
        $this->sesiones = $sesiones ?? new SesionService();
        $this->auditoria = $auditoria ?? new AuditoriaService();
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{
     *     usuarios: list<array<string, mixed>>,
     *     pager: \CodeIgniter\Pager\PagerInterface|null,
     *     filtros: array{q: string, rol: string, estado: string}
     * }
     */
    public function listar(array $filtros = []): array
    {
        $q = $filtros['q'] ?? '';
        $q = is_string($q) ? trim($q) : '';

        $rol = $filtros['rol'] ?? '';
        $rol = is_string($rol) && in_array($rol, ['propietario', 'admin'], true) ? $rol : '';

        $estado = $filtros['estado'] ?? '';
        $estado = is_string($estado) && in_array($estado, ['activo', 'bloqueado', 'baja'], true)
            ? $estado
            : '';

        $pagina = filter_var($filtros['page'] ?? null, FILTER_VALIDATE_INT);
        $pagina = $pagina !== false && $pagina !== null && $pagina > 0 ? $pagina : 1;

        $resultado = $this->usuarios->listar([
            'q' => $q,
            'rol' => $rol,
            'estado' => $estado,
        ], $pagina);

        return [
            'usuarios' => $resultado['usuarios'],
            'pager' => $resultado['pager'],
            'filtros' => [
                'q' => $q,
                'rol' => $rol,
                'estado' => $estado,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->usuarios->buscarPorId($id);
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, no_encontrado?: bool}
     */
    public function actualizar(
        int $idUsuario,
        int $idAdmin,
        string $nombre,
        string $mail,
        string $rol,
        string $estado,
    ): array {
        $usuario = $this->usuarios->buscarPorId($idUsuario);

        if ($usuario === null) {
            return ['exito' => false, 'errores' => [], 'no_encontrado' => true];
        }

        $nombre = trim($nombre);
        $mail = mb_strtolower(trim($mail), 'UTF-8');
        $rol = trim($rol);
        $estado = trim($estado);
        $errores = $this->validarDatosUsuario($nombre, $mail, $rol, $estado);

        if ($idUsuario === $idAdmin) {
            if ($rol !== 'admin') {
                $errores['rol'] = 'No puede quitarse el rol Administrador a sí mismo';
            }

            if ($estado !== 'activo') {
                $errores['estado'] = $estado === 'baja'
                    ? 'No puede darse de baja a sí mismo'
                    : 'No puede bloquearse a sí mismo';
            }
        }

        if ($errores !== []) {
            return ['exito' => false, 'errores' => $errores];
        }

        if ($this->usuarios->buscarPorMailDeOtroUsuario($mail, $idUsuario) !== null) {
            return [
                'exito' => false,
                'errores' => ['mail' => 'El email ya se encuentra registrado'],
            ];
        }

        try {
            $this->guardarCambio(
                $idUsuario,
                $idAdmin,
                [
                    'nombre_completo' => $nombre,
                    'mail' => $mail,
                    'rol' => $rol,
                    'estado' => $estado,
                ],
                in_array($estado, ['bloqueado', 'baja'], true),
            );
        } catch (DatabaseException $exception) {
            if ($this->usuarios->buscarPorMailDeOtroUsuario($mail, $idUsuario) !== null) {
                return [
                    'exito' => false,
                    'errores' => ['mail' => 'El email ya se encuentra registrado'],
                ];
            }

            throw $exception;
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, no_encontrado?: bool}
     */
    public function bloquear(int $idUsuario, int $idAdmin): array
    {
        return $this->cambiarEstado($idUsuario, $idAdmin, 'bloqueado');
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, no_encontrado?: bool}
     */
    public function darDeBaja(int $idUsuario, int $idAdmin): array
    {
        if ($idUsuario === $idAdmin) {
            return [
                'exito' => false,
                'errores' => ['estado' => 'No puede darse de baja a sí mismo'],
            ];
        }

        return $this->cambiarEstado($idUsuario, $idAdmin, 'baja');
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, no_encontrado?: bool}
     */
    public function reactivar(int $idUsuario, int $idAdmin): array
    {
        return $this->cambiarEstado($idUsuario, $idAdmin, 'activo');
    }

    /**
     * @return array{
     *     exito: bool,
     *     errores: array<string, string>,
     *     no_encontrado?: bool,
     *     contrasenia_temporal?: string
     * }
     */
    public function restablecerContrasenia(int $idUsuario, int $idAdmin): array
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);

        if ($usuario === null) {
            return ['exito' => false, 'errores' => [], 'no_encontrado' => true];
        }

        do {
            $contrasenia = 'A1' . bin2hex(random_bytes(16));
        } while (password_verify($contrasenia, (string) $usuario['contrasenia']));

        $algoritmo = in_array('argon2id', password_algos(), true)
            ? PASSWORD_ARGON2ID
            : PASSWORD_BCRYPT;
        $hash = password_hash($contrasenia, $algoritmo);

        if ($hash === false) {
            throw new RuntimeException('No se pudo proteger la contraseña temporal.');
        }

        $this->guardarCambio(
            $idUsuario,
            $idAdmin,
            [
                'contrasenia' => $hash,
                'cambiar_contrasenia' => 1,
            ],
            true,
        );

        return [
            'exito' => true,
            'errores' => [],
            'contrasenia_temporal' => $contrasenia,
        ];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>}
     */
    public function crear(string $nombre, string $mail, string $contrasenia, string $rol): array
    {
        $nombre = trim($nombre);
        $mail = mb_strtolower(trim($mail), 'UTF-8');
        $rol = trim($rol);
        $errores = [];

        if ($nombre === '') {
            $errores['nombre_completo'] = 'El nombre es obligatorio';
        } elseif (mb_strlen($nombre, 'UTF-8') > 50) {
            $errores['nombre_completo'] = 'El nombre no puede superar los 50 caracteres';
        }

        if ($mail === '') {
            $errores['mail'] = 'El email es obligatorio';
        } elseif (
            mb_strlen($mail, 'UTF-8') > 100
            || filter_var($mail, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errores['mail'] = 'El email debe tener formato válido';
        }

        if ($contrasenia === '') {
            $errores['contrasenia'] = 'La contraseña es obligatoria';
        } elseif (mb_strlen($contrasenia, 'UTF-8') < 8) {
            $errores['contrasenia'] = 'Mínimo 8 caracteres de contraseña';
        } elseif (! preg_match('/[A-Za-z]/', $contrasenia) || ! preg_match('/\d/', $contrasenia)) {
            $errores['contrasenia'] = 'Debe contener al menos una letra y un número';
        }

        if ($rol === '') {
            $errores['rol'] = 'El rol es obligatorio';
        } elseif (! in_array($rol, ['admin', 'propietario'], true)) {
            $errores['rol'] = 'Rol inválido';
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

        try {
            $creado = $this->usuarios->crear([
                'nombre_completo'     => $nombre,
                'mail'                => $mail,
                'contrasenia'         => $hash,
                'rol'                 => $rol,
                'estado'              => 'activo',
                'cambiar_contrasenia' => 0,
            ]);
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

            throw new RuntimeException('No se pudo crear el usuario. Intente nuevamente.');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, no_encontrado?: bool}
     */
    private function cambiarEstado(int $idUsuario, int $idAdmin, string $estado): array
    {
        $usuario = $this->usuarios->buscarPorId($idUsuario);

        if ($usuario === null) {
            return ['exito' => false, 'errores' => [], 'no_encontrado' => true];
        }

        if ($idUsuario === $idAdmin && $estado !== 'activo') {
            $mensaje = $estado === 'baja'
                ? 'No puede darse de baja a sí mismo'
                : 'No puede bloquearse a sí mismo';

            return ['exito' => false, 'errores' => ['estado' => $mensaje]];
        }

        if ($usuario['estado'] === $estado) {
            return ['exito' => true, 'errores' => []];
        }

        $this->guardarCambio(
            $idUsuario,
            $idAdmin,
            ['estado' => $estado],
            in_array($estado, ['bloqueado', 'baja'], true),
        );

        return ['exito' => true, 'errores' => []];
    }

    /**
     * @return array<string, string>
     */
    private function validarDatosUsuario(string $nombre, string $mail, string $rol, string $estado): array
    {
        $errores = [];

        if ($nombre === '') {
            $errores['nombre_completo'] = 'El nombre es obligatorio';
        } elseif (mb_strlen($nombre, 'UTF-8') > 50) {
            $errores['nombre_completo'] = 'El nombre no puede superar los 50 caracteres';
        }

        if ($mail === '') {
            $errores['mail'] = 'El email es obligatorio';
        } elseif (
            mb_strlen($mail, 'UTF-8') > 100
            || filter_var($mail, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errores['mail'] = 'El email debe tener formato válido';
        }

        if (! in_array($rol, ['admin', 'propietario'], true)) {
            $errores['rol'] = $rol === '' ? 'El rol es obligatorio' : 'Rol inválido';
        }

        if (! in_array($estado, ['activo', 'bloqueado', 'baja'], true)) {
            $errores['estado'] = $estado === '' ? 'El estado es obligatorio' : 'Estado inválido';
        }

        return $errores;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function guardarCambio(int $idUsuario, int $idAdmin, array $datos, bool $invalidarSesiones): void
    {
        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la modificación del usuario.');
        }

        try {
            if (! $this->usuarios->actualizar($idUsuario, $datos)) {
                throw new RuntimeException('No se pudo actualizar el usuario.');
            }

            if ($invalidarSesiones && ! $this->sesiones->invalidarPorUsuario($idUsuario)) {
                throw new RuntimeException('No se pudieron invalidar las sesiones del usuario.');
            }

            $this->auditoria->registrarModificacion($idUsuario, $idAdmin);

            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar la modificación del usuario.');
            }
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }
}
