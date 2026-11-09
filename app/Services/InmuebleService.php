<?php

namespace App\Services;

use App\Repositories\PropiedadRepository;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class InmuebleService
{
    private const ESTADOS = ['ocupado', 'disponible', 'en mantenimiento'];

    private const TIPOS = ['casa', 'departamento', 'local comercial', 'oficina', 'galpon'];

    private BaseConnection $db;

    public function __construct(
        private ?PropiedadRepository $propiedades = null,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->propiedades ??= new PropiedadRepository();
    }

    /**
     * @return array{tipos: list<array<string, mixed>>, propietarios: list<array<string, mixed>>}
     */
    public function opcionesFormulario(string $rol): array
    {
        return [
            'tipos' => $this->propiedades->listarTipos(),
            'propietarios' => $rol === 'admin'
                ? $this->propiedades->listarPropietariosActivos()
                : [],
        ];
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array{exito: bool, errores: array<string, string>, datos: array<string, string>}
     */
    public function crear(array $entrada, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarDatos($entrada);
        $errores = $this->validarDatos($datos);

        if (! in_array($rol, ['admin', 'propietario'], true) || $idUsuario < 1) {
            return ['exito' => false, 'errores' => ['general' => 'No tiene permisos para esta acción'], 'datos' => $datos];
        }

        $idPropietario = $idUsuario;

        if ($rol === 'admin') {
            $idIngresado = filter_var($datos['id_propietario'], FILTER_VALIDATE_INT);

            if ($idIngresado === false || $idIngresado < 1) {
                $errores['id_propietario'] = 'El propietario es obligatorio.';
            } elseif (! $this->propiedades->esPropietarioActivo((int) $idIngresado)) {
                $errores['id_propietario'] = 'Debe seleccionar un propietario activo.';
            } else {
                $idPropietario = (int) $idIngresado;
            }
        }

        if ($errores !== []) {
            return ['exito' => false, 'errores' => $errores, 'datos' => $datos];
        }

        if ($this->propiedades->existeAliasParaPropietario($datos['alias'], $idPropietario)) {
            return $this->aliasDuplicado($datos);
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar el alta del inmueble.');
        }

        try {
            $idDireccion = $this->propiedades->crearDireccion([
                'calle' => $datos['calle'],
                'numero' => (int) $datos['numero'],
                'piso' => $datos['piso'] === '' ? null : (int) $datos['piso'],
                'depto' => $datos['depto'] === '' ? null : $datos['depto'],
                'ciudad' => $datos['ciudad'],
            ]);

            if ($idDireccion < 1) {
                throw new RuntimeException('No se pudo guardar la dirección del inmueble.');
            }

            $idPropiedad = $this->propiedades->crear([
                'alias' => $datos['alias'],
                'superficie' => $datos['superficie'],
                'estado' => $datos['estado'],
                'id_tipo_inmueble' => (int) $datos['id_tipo_inmueble'],
                'id_propietario' => $idPropietario,
                'id_direccion' => $idDireccion,
            ]);

            if ($idPropiedad < 1 || ! $this->db->transStatus()) {
                throw new RuntimeException('No se pudo guardar el inmueble.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar el alta del inmueble.');
            }
        } catch (Throwable $exception) {
            $this->db->transRollback();

            if ($this->propiedades->existeAliasParaPropietario($datos['alias'], $idPropietario)) {
                return $this->aliasDuplicado($datos);
            }

            throw $exception;
        }

        return ['exito' => true, 'errores' => [], 'datos' => $datos];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{
     *     inmuebles: list<array<string, mixed>>,
     *     pager: \CodeIgniter\Pager\PagerInterface|null,
     *     filtros: array{ciudad: string, tipo: string, estado: string, q: string, propietario: string},
     *     propietarios: list<array<string, mixed>>,
     *     sin_permiso: bool
     * }
     */
    public function listar(array $filtros, int $idUsuario, string $rol): array
    {
        if (! in_array($rol, ['admin', 'propietario'], true) || $idUsuario < 1) {
            return [
                'inmuebles' => [],
                'pager' => null,
                'filtros' => $this->normalizarFiltros($filtros),
                'propietarios' => [],
                'sin_permiso' => true,
            ];
        }

        $filtrosNormalizados = $this->normalizarFiltros($filtros);
        $pagina = filter_var($filtros['page'] ?? null, FILTER_VALIDATE_INT);
        $pagina = $pagina !== false && $pagina !== null && $pagina > 0 ? $pagina : 1;
        $idPropietario = $rol === 'propietario' ? $idUsuario : null;

        $resultado = $this->propiedades->listar($filtrosNormalizados, $pagina, $idPropietario);

        return [
            'inmuebles' => $resultado['inmuebles'],
            'pager' => $resultado['pager'],
            'filtros' => $filtrosNormalizados,
            'propietarios' => $rol === 'admin' ? $this->propiedades->listarPropietarios() : [],
            'sin_permiso' => false,
        ];
    }

    /**
     * @return array{inmueble: array<string, mixed>|null, servicios: list<array<string, mixed>>, sin_permiso: bool}
     */
    public function detalle(int $idPropiedad, int $idUsuario, string $rol): array
    {
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);

        if ($inmueble === null) {
            return ['inmueble' => null, 'servicios' => [], 'sin_permiso' => false];
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return ['inmueble' => null, 'servicios' => [], 'sin_permiso' => true];
        }

        return [
            'inmueble' => $inmueble,
            'servicios' => $this->propiedades->listarServicios($idPropiedad),
            'sin_permiso' => false,
        ];
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array{
     *     exito: bool,
     *     errores: array<string, string>,
     *     datos: array<string, string>,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function actualizar(int $idPropiedad, array $entrada, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarDatos($entrada);
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);

        if ($inmueble === null) {
            return [
                'exito' => false,
                'errores' => [],
                'datos' => $datos,
                'sin_permiso' => false,
                'no_encontrado' => true,
            ];
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return [
                'exito' => false,
                'errores' => [],
                'datos' => $datos,
                'sin_permiso' => true,
                'no_encontrado' => false,
            ];
        }

        $inactivo = $inmueble['estado'] === 'inactivo';

        if ($inactivo && $datos['estado'] === '') {
            $datos['estado'] = 'inactivo';
        }

        $errores = $this->validarDatos($datos, $inactivo);

        if ($inactivo && $datos['estado'] !== 'inactivo') {
            $errores['estado'] = 'Estado inválido';
        }

        if ($errores !== []) {
            return [
                'exito' => false,
                'errores' => $errores,
                'datos' => $datos,
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        if ($this->propiedades->existeAliasParaPropietario(
            $datos['alias'],
            (int) $inmueble['id_propietario'],
            $idPropiedad,
        )) {
            return [
                'exito' => false,
                'errores' => ['alias' => 'El alias ya está registrado para este propietario.'],
                'datos' => $datos,
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la modificación del inmueble.');
        }

        try {
            $direccionActualizada = $this->propiedades->actualizarDireccion(
                (int) $inmueble['id_direccion'],
                [
                    'calle' => $datos['calle'],
                    'numero' => (int) $datos['numero'],
                    'piso' => $datos['piso'] === '' ? null : (int) $datos['piso'],
                    'depto' => $datos['depto'] === '' ? null : $datos['depto'],
                    'ciudad' => $datos['ciudad'],
                ],
            );
            $propiedadActualizada = $this->propiedades->actualizar($idPropiedad, [
                'alias' => $datos['alias'],
                'superficie' => $datos['superficie'],
                'estado' => $datos['estado'],
                'id_tipo_inmueble' => (int) $datos['id_tipo_inmueble'],
            ]);

            if (! $direccionActualizada || ! $propiedadActualizada || ! $this->db->transStatus()) {
                throw new RuntimeException('No se pudo guardar la modificación del inmueble.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar la modificación del inmueble.');
            }
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }

        return [
            'exito' => true,
            'errores' => [],
            'datos' => $datos,
            'sin_permiso' => false,
            'no_encontrado' => false,
        ];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, sin_permiso: bool, no_encontrado: bool}
     */
    public function darDeBaja(int $idPropiedad, int $idUsuario, string $rol): array
    {
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);
        $resultado = $this->resultadoEstado($inmueble, $idUsuario, $rol);

        if ($resultado !== null) {
            return $resultado;
        }

        if ($inmueble['estado'] === 'inactivo') {
            return [
                'exito' => false,
                'errores' => ['general' => 'El inmueble ya está inactivo.'],
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        if (! $this->propiedades->actualizar($idPropiedad, ['estado' => 'inactivo'])) {
            throw new RuntimeException('No se pudo dar de baja el inmueble.');
        }

        return [
            'exito' => true,
            'errores' => [],
            'sin_permiso' => false,
            'no_encontrado' => false,
        ];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, sin_permiso: bool, no_encontrado: bool}
     */
    public function reactivar(int $idPropiedad, int $idUsuario, string $rol): array
    {
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);
        $resultado = $this->resultadoEstado($inmueble, $idUsuario, $rol);

        if ($resultado !== null) {
            return $resultado;
        }

        if ($inmueble['estado'] !== 'inactivo') {
            return [
                'exito' => false,
                'errores' => ['general' => 'El inmueble no está inactivo.'],
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        if (! $this->propiedades->actualizar($idPropiedad, ['estado' => 'disponible'])) {
            throw new RuntimeException('No se pudo reactivar el inmueble.');
        }

        return [
            'exito' => true,
            'errores' => [],
            'sin_permiso' => false,
            'no_encontrado' => false,
        ];
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array<string, string>
     */
    private function normalizarDatos(array $entrada): array
    {
        $datos = [];

        foreach ([
            'alias',
            'calle',
            'numero',
            'piso',
            'depto',
            'ciudad',
            'id_tipo_inmueble',
            'superficie',
            'estado',
            'id_propietario',
        ] as $campo) {
            $valor = $entrada[$campo] ?? '';
            $datos[$campo] = is_string($valor) || is_numeric($valor) ? trim((string) $valor) : '';
        }

        $datos['superficie'] = str_replace(',', '.', $datos['superficie']);

        return $datos;
    }

    /**
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function validarDatos(array $datos, bool $permiteInactivo = false): array
    {
        $errores = [];
        $obligatorios = [
            'alias' => 'El alias es obligatorio',
            'calle' => 'La calle es obligatoria',
            'numero' => 'El número es obligatorio',
            'ciudad' => 'La ciudad es obligatoria',
            'id_tipo_inmueble' => 'El tipo es obligatorio',
            'superficie' => 'La superficie debe ser numérica',
            'estado' => 'El estado es obligatorio',
        ];

        foreach ($obligatorios as $campo => $mensaje) {
            if ($datos[$campo] === '') {
                $errores[$campo] = $mensaje;
            }
        }

        foreach (['numero', 'piso'] as $campo) {
            if ($datos[$campo] !== '' && ! $this->esEnteroValido($datos[$campo])) {
                $errores[$campo] = 'El número ingresado es invalido. Por favor vuelva a intentarlo.';
            }
        }

        foreach (['alias' => 45, 'calle' => 45, 'depto' => 10, 'ciudad' => 60] as $campo => $maximo) {
            if (mb_strlen($datos[$campo], 'UTF-8') > $maximo) {
                $errores[$campo] = 'El valor ingresado supera la longitud permitida.';
            }
        }

        if ($datos['id_tipo_inmueble'] !== '') {
            $idTipo = filter_var($datos['id_tipo_inmueble'], FILTER_VALIDATE_INT);

            if ($idTipo === false || $idTipo < 1 || ! $this->propiedades->existeTipo((int) $idTipo)) {
                $errores['id_tipo_inmueble'] = 'Tipo de inmueble inválido';
            }
        }

        if ($datos['estado'] !== ''
            && ! in_array($datos['estado'], self::ESTADOS, true)
            && ! ($permiteInactivo && $datos['estado'] === 'inactivo')) {
            $errores['estado'] = 'Estado inválido';
        }

        if ($datos['superficie'] !== '') {
            if (! preg_match('/\A[+-]?(?:\d+(?:\.\d*)?|\.\d+)\z/', $datos['superficie'])) {
                $errores['superficie'] = 'La superficie debe ser numérica';
            } elseif ((float) $datos['superficie'] <= 0) {
                $errores['superficie'] = 'La superficie debe ser > 0';
            } elseif (str_contains($datos['superficie'], '.')
                && strlen(substr(strrchr($datos['superficie'], '.'), 1)) > 2) {
                $errores['superficie'] = 'La superficie admite hasta 2 decimales';
            } elseif (strlen(ltrim(explode('.', ltrim($datos['superficie'], '+-'))[0], '0')) > 8) {
                $errores['superficie'] = 'La superficie supera el máximo permitido.';
            }
        }

        return $errores;
    }

    /**
     * @param array<string, mixed>|null $inmueble
     * @return array{exito: false, errores: array<string, string>, sin_permiso: bool, no_encontrado: bool}|null
     */
    private function resultadoEstado(?array $inmueble, int $idUsuario, string $rol): ?array
    {
        if ($inmueble === null) {
            return [
                'exito' => false,
                'errores' => [],
                'sin_permiso' => false,
                'no_encontrado' => true,
            ];
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return [
                'exito' => false,
                'errores' => [],
                'sin_permiso' => true,
                'no_encontrado' => false,
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $inmueble
     */
    private function puedeGestionar(array $inmueble, int $idUsuario, string $rol): bool
    {
        if ($idUsuario < 1) {
            return false;
        }

        if ($rol === 'admin') {
            return true;
        }

        return $rol === 'propietario'
            && (int) $inmueble['id_propietario'] === $idUsuario;
    }

    private function esEnteroValido(string $valor): bool
    {
        if (! preg_match('/\A[+-]?\d+\z/', $valor)) {
            return false;
        }

        $negativo = str_starts_with($valor, '-');
        $digitos = ltrim($valor, '+-');
        $digitos = ltrim($digitos, '0');
        $digitos = $digitos === '' ? '0' : $digitos;
        $maximo = $negativo ? '2147483648' : '2147483647';

        return strlen($digitos) < strlen($maximo)
            || (strlen($digitos) === strlen($maximo) && strcmp($digitos, $maximo) <= 0);
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{ciudad: string, tipo: string, estado: string, q: string, propietario: string}
     */
    private function normalizarFiltros(array $filtros): array
    {
        $ciudad = $filtros['ciudad'] ?? '';
        $ciudad = is_string($ciudad) ? trim($ciudad) : '';
        $q = $filtros['q'] ?? '';
        $q = is_string($q) ? trim($q) : '';
        $tipo = $filtros['tipo'] ?? '';
        $tipo = is_string($tipo) && in_array($tipo, self::TIPOS, true) ? $tipo : '';
        $estado = $filtros['estado'] ?? '';
        $estado = is_string($estado) && in_array($estado, [...self::ESTADOS, 'inactivo'], true) ? $estado : '';
        $propietario = filter_var($filtros['propietario'] ?? null, FILTER_VALIDATE_INT);

        return [
            'ciudad' => mb_substr($ciudad, 0, 60, 'UTF-8'),
            'tipo' => $tipo,
            'estado' => $estado,
            'q' => mb_substr($q, 0, 100, 'UTF-8'),
            'propietario' => $propietario !== false && $propietario !== null && $propietario > 0
                ? (string) $propietario
                : '',
        ];
    }

    /**
     * @param array<string, string> $datos
     * @return array{exito: false, errores: array<string, string>, datos: array<string, string>}
     */
    private function aliasDuplicado(array $datos): array
    {
        return [
            'exito' => false,
            'errores' => ['alias' => 'El alias ya está registrado para este propietario.'],
            'datos' => $datos,
        ];
    }
}
